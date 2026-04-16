<?php
/**
 * PW_OpenAI_Client — формирование промпта и запрос к gpt-4o
 * Промпт строго по ТЗ §6
 */
class PW_OpenAI_Client {
 
    private $api_key;
    const API_URL  = 'https://api.openai.com/v1/chat/completions';
    const MODEL    = 'gpt-4o';
    const MAX_RETRY = 1;
 
    public function __construct() {
        $this->api_key = get_option( 'payway_openai_api_key', '' );
    }
 
    /**
     * Запускает анализ и возвращает валидированный JSON-ответ.
     *
     * @param array $channel_data   Данные YouTube (channel + videos)
     * @param array $analyzer_data  Результат PW_Audit_Analyzer::analyze()
     * @return array|WP_Error
     */
    public function analyze( array $channel_data, array $analyzer_data ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', 'OpenAI API key не настроен' );
        }
 
        $user_message = $this->build_user_message( $channel_data, $analyzer_data );
        $messages = [
            [ 'role' => 'system', 'content' => $this->get_system_prompt() ],
            [ 'role' => 'user',   'content' => $user_message ],
        ];
 
        // Первая попытка
        $result = $this->call_api( $messages );
        if ( is_wp_error( $result ) ) {
            // Retry
            $result = $this->call_api( $messages );
        }
 
        if ( is_wp_error( $result ) ) {
            return $result;
        }
 
        // Валидация JSON-схемы
        $validated = $this->validate_response( $result );
        if ( is_wp_error( $validated ) ) {
            // Retry с явным указанием на ошибку
            $messages[] = [ 'role' => 'assistant', 'content' => json_encode( $result ) ];
            $messages[] = [ 'role' => 'user', 'content' => 'Ответ не соответствует требуемой JSON-схеме. Повтори ответ строго по схеме, без лишних ключей.' ];
            $result2    = $this->call_api( $messages );
            if ( ! is_wp_error( $result2 ) ) {
                $validated = $this->validate_response( $result2 );
            }
        }
 
        if ( is_wp_error( $validated ) ) {
            return new WP_Error( 'ai_validation_failed', 'AI вернул невалидный формат JSON: ' . $validated->get_error_message() );
        }
 
        return $validated;
    }
 
    // ─────────────────────────────────────────────────────────
    // SYSTEM PROMPT (строго по ТЗ §6.1)
    // ─────────────────────────────────────────────────────────
 
    private function get_system_prompt() {
        return <<<'PROMPT'
Ты — эксперт по модерации YouTube-каналов для сервиса вывода доходов AdSense.
 
Твоя задача — провести комплексный аудит YouTube-канала по трём направлениям:
принятие в программу монетизации, риски демонетизации и риски авторских прав.
 
## Входные данные о канале
 
Тебе будут предоставлены следующие данные (получены через YouTube Data API v3):
- Название и URL канала
- Дата создания канала
- Количество подписчиков
- Общее количество просмотров
- Количество видео
- Статус madeForKids (true/false)
- Категории канала (topicCategories)
- Страна канала
- Статус longUploadsStatus (allowed / disallowed / eligible)
- Список последних 20 видео: название, дата публикации, количество просмотров, лайков, комментариев, длительность, теги
- Частота публикаций (видео в месяц за последние 3 месяца)
- Описание канала и ключевые слова (keywords)
- PHP-сигналы reused content (вычислены автоматически)
 
## Что нужно оценить
 
### Блок 2. Риски демонетизации
 
Оцени вероятность проблем с монетизацией после подключения.
 
Анализируй следующие сигналы риска:
 
**Контентные риски:**
- Названия и теги видео содержат стоп-слова AdSense: оружие, наркотики, насилие, смерть, катастрофы, секс, война, алкоголь, азартные игры, спорные политические темы
- Видео с аномально низким ER (лайки/просмотры < 0.5%) при большом охвате — признак неорганического трафика или кликбейта
- Резкие скачки просмотров на отдельных видео без объяснимой причины (возможная накрутка)
- Очень короткие видео (< 2 минут) составляют более 50% контента — низкий рекламный инвентарь
 
**Reused / Mass-produced content (высокий приоритет):**
Это отдельная категория рисков — YouTube прямо указывает на следующие паттерны как основание для демонетизации всего канала:
1. Однотипные названия по шаблону (≥ 40% видео одного паттерна)
2. Одинаковая длительность видео (≥ 60% в диапазоне ±30 сек)
3. Аномально высокая частота публикаций (>20 видео/мес при <100k подписчиков)
4. Тематика с высоким риском reused content: чтение новостей, компиляции, реакции, мемы, перезаливы, AI-контент, стоковое видео+озвучка
5. Крайне низкий ER при высоком числе видео (ER < 1% + videoCount > 200)
6. Прямые слова в названиях: «компиляция», «нарезка», «реакция», «перезалив» → высокий уровень автоматически
 
Уровень риска по категории reused:
- 1 сигнал → Средний
- 2+ сигнала → Высокий
- Прямые слова → Высокий автоматически
 
**Структурные риски:**
- Нерегулярные публикации (пропуски > 30 дней)
- Резкое падение частоты публикаций в последние 2 месяца
- Канал молодой (6–12 месяцев) с неустоявшейся аудиторией
 
**Репутационные риски:**
- Категория канала относится к ограниченно монетизируемым нишам: новости, политика, трагедии, конфликты, псевдонаука, финансовые советы
- Ключевые слова канала содержат чувствительные темы
 
### Блок 3. Риски авторских прав и нарушений правил
 
Оцени вероятность получения страйков.
 
**Авторские права (Content ID / ручные жалобы):**
- Названия видео содержат упоминания брендов, фильмов, сериалов, игр, музыкальных исполнителей — признак возможного использования чужого контента
- Теги содержат названия популярных франшиз (Marvel, Disney, Nintendo, музыкальные лейблы)
- Тематика канала (реакции, обзоры фильмов/игр, летсплеи, разборы клипов) подразумевает использование фрагментов чужого контента
- Кавер-каналы, компиляции «лучших моментов», «топы» с чужим видео
 
**Нарушения правил сообщества:**
- Тематика граничит с запрещённым контентом: экстремизм, насилие, буллинг, шок-контент, дезинформация
- Названия видео содержат провокационные или вводящие в заблуждение формулировки
- Канал специализируется на разоблачениях, скандалах, конфликтах с другими блогерами
 
**Спам и искусственное продвижение:**
- Аномальное соотношение подписчиков к просмотрам (подписчиков >> просмотров — признак накрутки)
- Очень высокий ER (> 15%) при малом числе просмотров — возможна накрутка лайков
 
## Итоговый вердикт
 
На основе всех данных и PHP-сигналов reused content определи:
- Если хотя бы 1 критерий блока 1 получил FAIL (возраст, регулярность, детский контент, мин. видео) → verdict = "reject"
- Если PHP-сигналы содержат 2+ HIGH → verdict = "manual" (даже при прохождении блока 1)
- Если block2_risk = "high" → verdict = "manual"
- Если всё в порядке → verdict = "accept"
 
## Важные правила
 
- Если данных недостаточно для оценки — пиши "требует ручной проверки", не додумывай
- Не принимай решение "accept", если block1_fail = true
- Оценивай консервативно. Учитывай русскоязычный контекст
- Ошибка в сторону «принять рискованный канал» опаснее, чем «отклонить пограничный»
- Для каждого найденного риска ОБЯЗАТЕЛЬНО укажи конкретный сигнал (название видео, тег, метрику) — не общие слова, а конкретику из данных
- Всегда указывай конкретные числа из переданных данных в своих рекомендациях. Никогда не пиши общих фраз вроде «улучшите качество» или «разнообразьте контент» без конкретики. Если ER низкий — укажи точное значение и норму. Если длина одинакова — укажи конкретную длительность из данных.
 
Отвечай СТРОГО в формате JSON — без markdown, без пояснений вне JSON. Структура:
{
  "verdict": "accept" | "reject" | "manual",
  "verdict_reason": "1-2 предложения обоснования вердикта",
  "priority_action": "Конкретное главное действие для пользователя. Если verdict=reject по возрасту — включить дату retry_date из данных.",
  "retry_context": "1-2 предложения объяснения почему именно этот срок (только если есть retry_date, иначе пустая строка).",
  "block2_risk": "low" | "medium" | "high",
  "block2_signals": [
    {
      "level": "high" | "medium" | "low",
      "title": "Краткое название сигнала",
      "description": "Конкретные данные: какие видео, теги, метрики",
      "recommendation": "Что нужно сделать автору",
      "issue_type": "тип сигнала (duration_uniformity | title_pattern | high_freq_low_er | reused_keywords | другой)"
    }
  ],
  "block3_risk": "low" | "medium" | "high",
  "block3_signals": [
    {
      "level": "high" | "medium" | "low",
      "title": "Краткое название сигнала",
      "description": "Конкретные данные",
      "recommendation": "Что нужно сделать автору"
    }
  ],
  "summary": "2-4 предложения итог",
  "admission": {
    "risk": "low" | "medium" | "high",
    "details": "краткое описание"
  },
  "demonetization": {
    "risk": "low" | "medium" | "high",
    "details": "краткое описание"
  },
  "copyright": {
    "risk": "low" | "medium" | "high",
    "details": "краткое описание"
  },
  "summary_for_moderator": "2-4 предложения для модератора: что проверить вручную, на что обратить внимание",
  "checklist_moderator": ["Шаг 1: конкретное действие", "Шаг 2: конкретное действие", "Шаг 3", "Шаг 4"],
  "metric_explanations": {
    "er": "Объяснение ER простым языком с конкретными числами",
    "duration": "Объяснение одинаковой длины если есть (иначе пустая строка)",
    "frequency": "Объяснение частоты публикаций"
  },
  "content_allowed": ["Что разрешено с данным контентом (если есть block3_signals, иначе пустой массив)"],
  "content_forbidden": ["Что запрещено (если есть block3_signals, иначе пустой массив)"],
  "recommendations_for_user": [
    {"title": "Краткий заголовок", "text": "Подробное описание с конкретными числами", "tag": "critical"},
    {"title": "Заголовок", "text": "Описание", "tag": "important"},
    {"title": "Заголовок", "text": "Описание", "tag": "recommended"}
  ]
}
 
Поле tag в recommendations_for_user: "critical" — критично, "important" — важно, "recommended" — рекомендуется.
PROMPT;
    }
 
    // ─────────────────────────────────────────────────────────
    // USER MESSAGE (§6.2)
    // ─────────────────────────────────────────────────────────
 
    private function build_user_message( array $yt_data, array $ad ) {
        $ch      = $yt_data['channel'];
        $videos  = $yt_data['videos'];
        $snippet = $ch['snippet'] ?? [];
        $stats   = $ch['statistics'] ?? [];
        $status  = $ch['status'] ?? [];
        $topics  = $ch['topicDetails']['topicCategories'] ?? [];
        $metrics = $ad['channel_metrics'] ?? [];
 
        // Форматируем список видео
        $video_lines = [];
        foreach ( $videos as $i => $v ) {
            $dur     = gmdate( 'H:i:s', $v['duration_sec'] );
            $tags    = implode( ', ', array_slice( $v['tags'] ?? [], 0, 10 ) );
            $er      = $v['viewCount'] > 0 ? round( ( $v['likeCount'] / $v['viewCount'] ) * 100, 2 ) . '%' : 'n/a';
            $num     = $i + 1;
            $pub     = date( 'd.m.Y', strtotime( $v['publishedAt'] ) );
            $video_lines[] = "{$num}. \"{$v['title']}\" | {$pub} | {$v['viewCount']} просмотров | {$v['likeCount']} лайков | ER: {$er} | {$dur} | Теги: {$tags}";
        }
 
        // PHP-сигналы
        $signal_lines = [];
        foreach ( $ad['php_signals'] as $s ) {
            $signal_lines[] = "- [{$s['level']}] {$s['title']}: {$s['detail']}";
        }
        $signals_text = $signal_lines ? implode( "\n", $signal_lines ) : 'Сигналов reused content не обнаружено';
 
        // Критерии блока 1
        $block1_lines = [];
        foreach ( $ad['block1_criteria'] as $c ) {
            $icon = $c['status'] === 'ok' ? '✅' : ( $c['status'] === 'warn' ? '⚠️' : '❌' );
            $block1_lines[] = "{$icon} {$c['name']}: {$c['detail']}";
        }
        $block1_text = implode( "\n", $block1_lines );
 
        $subs   = number_format( (int) ( $stats['subscriberCount'] ?? 0 ) );
        $views  = number_format( (int) ( $stats['viewCount'] ?? 0 ) );
        $vcnt   = number_format( (int) ( $stats['videoCount'] ?? 0 ) );
        $mfk    = ( $status['madeForKids'] ?? false ) ? 'true' : 'false';
        $lus    = $status['longUploadsStatus'] ?? 'unknown';
        $age    = $ad['age_months'];
        $vpm    = $ad['videos_per_month'];
        $er_avg = $ad['avg_er'];
        $b1stat = $ad['block1_status'];
        $b1fail = ( $b1stat === 'fail' ) ? 'true' : 'false';
        $topics_str = implode( ', ', $topics ) ?: 'не указаны';
        $video_list = implode( "\n", $video_lines );
 
        // Sprint 1: retry_date и retry_months_left для AI
        $retry_date        = $metrics['retry_date'] ?? '';
        $retry_months_left = $metrics['retry_months_left'] ?? 0;
        $retry_info = '';
        if ( ! empty( $retry_date ) ) {
            $retry_info = "\n- Дата повторной подачи: {$retry_date} (осталось {$retry_months_left} мес.)";
        }
 
        // Sprint 1: список видео с метриками для AI
        $videos_list = $metrics['videos_list'] ?? [];
        $videos_summary = '';
        if ( ! empty( $videos_list ) ) {
            $videos_count = count( $videos_list );
            $videos_summary = "\n\nПоследние видео канала (список из {$videos_count} видео):\n";
            foreach ( $videos_list as $i => $v ) {
                $num = $i + 1;
                $videos_summary .= "{$num}. {$v['title']} | просм: {$v['view_count']} | ER: {$v['er']}% | длина: {$v['duration_fmt']}\n";
            }
        }
 
        return <<<MSG
## Данные канала для аудита
 
**Основное:**
- Название: {$snippet['title']}
- URL: {$snippet['customUrl']}
- Страна: {$snippet['country']}
- Дата создания: {$snippet['publishedAt']}
- Возраст канала: {$age} мес.{$retry_info}
 
**Статистика:**
- Подписчики: {$subs}
- Просмотры всего: {$views}
- Видео всего: {$vcnt}
- Видео/мес (3 мес): {$vpm}
- Средний ER: {$er_avg}%
 
**Статус:**
- madeForKids: {$mfk}
- longUploadsStatus: {$lus}
- Категории (topicCategories): {$topics_str}
 
**Описание канала:**
{$snippet['description']}
 
---
 
## PHP-проверка Блок 1 (допуск) — результаты
 
block1_fail = {$b1fail}
 
{$block1_text}
 
---
 
## PHP-сигналы reused / mass-produced content
 
{$signals_text}
 
---
 
## Последние 20 видео
 
{$video_list}
{$videos_summary}
---
 
Проведи аудит Блока 2 (риски демонетизации) и Блока 3 (авторские права).
Верни JSON строго по схеме из system message. Учти block1_fail при формировании verdict.
MSG;
    }
 
    // ─────────────────────────────────────────────────────────
    // API-запрос
    // ─────────────────────────────────────────────────────────
 
    private function call_api( array $messages ) {
        $body = wp_json_encode([
            'model'       => self::MODEL,
            'messages'    => $messages,
            'temperature' => 0.2,
            'max_tokens'  => 3000,
            'response_format' => [ 'type' => 'json_object' ],
        ]);
 
        $response = wp_remote_post( self::API_URL, [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => $body,
        ]);
 
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'openai_http', $response->get_error_message() );
        }
 
        $code    = wp_remote_retrieve_response_code( $response );
        $raw     = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $raw, true );
 
        if ( $code !== 200 ) {
            $msg = $decoded['error']['message'] ?? "HTTP {$code}";
            return new WP_Error( 'openai_api', 'OpenAI API: ' . $msg );
        }
 
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        if ( empty( $content ) ) {
            return new WP_Error( 'openai_empty', 'Пустой ответ от OpenAI' );
        }
 
        $parsed = json_decode( $content, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'openai_json', 'Не удалось распарсить JSON из ответа OpenAI' );
        }
 
        return $parsed;
    }
 
    // ─────────────────────────────────────────────────────────
    // Валидация JSON-схемы (§6.3)
    // ─────────────────────────────────────────────────────────
 
    private function validate_response( array $data ) {
        $required = [
            'verdict', 'verdict_reason', 'block2_risk', 'block2_signals',
            'block3_risk', 'block3_signals', 'summary_for_moderator', 'recommendations_for_user',
        ];
 
        foreach ( $required as $field ) {
            if ( ! array_key_exists( $field, $data ) ) {
                return new WP_Error( 'missing_field', "Отсутствует поле: {$field}" );
            }
        }
 
        $valid_verdicts = [ 'accept', 'reject', 'manual' ];
        if ( ! in_array( $data['verdict'], $valid_verdicts, true ) ) {
            return new WP_Error( 'invalid_verdict', 'Невалидный verdict: ' . $data['verdict'] );
        }
 
        $valid_risks = [ 'low', 'medium', 'high' ];
        foreach ( [ 'block2_risk', 'block3_risk' ] as $field ) {
            if ( ! in_array( $data[ $field ], $valid_risks, true ) ) {
                return new WP_Error( 'invalid_risk', "Невалидный уровень риска в {$field}: " . $data[ $field ] );
            }
        }
 
        // Нормализуем массивы сигналов
        foreach ( [ 'block2_signals', 'block3_signals' ] as $field ) {
            if ( ! is_array( $data[ $field ] ) ) {
                $data[ $field ] = [];
            }
            foreach ( $data[ $field ] as &$sig ) {
                $sig['level']          = $sig['level'] ?? 'medium';
                $sig['title']          = $sig['title'] ?? '';
                $sig['description']    = $sig['description'] ?? '';
                $sig['recommendation'] = $sig['recommendation'] ?? '';
            }
            unset( $sig );
        }
 
        // Sprint 1: нормализация recommendations_for_user (поддержка старого и нового формата)
        if ( ! is_array( $data['recommendations_for_user'] ) ) {
            $data['recommendations_for_user'] = [];
        }
        // Нормализуем: если элемент строка — конвертируем в объект
        foreach ( $data['recommendations_for_user'] as &$rec ) {
            if ( is_string( $rec ) ) {
                $rec = [
                    'title' => mb_substr( $rec, 0, 60 ),
                    'text'  => $rec,
                    'tag'   => 'recommended',
                ];
            } else {
                $rec['title'] = $rec['title'] ?? '';
                $rec['text']  = $rec['text'] ?? '';
                $rec['tag']   = $rec['tag'] ?? 'recommended';
            }
        }
        unset( $rec );
 
        // Sprint 1: новые поля — устанавливаем дефолты если AI не вернул
        $data['priority_action']     = $data['priority_action'] ?? '';
        $data['retry_context']       = $data['retry_context'] ?? '';
        $data['checklist_moderator'] = $data['checklist_moderator'] ?? [];
        $data['metric_explanations'] = $data['metric_explanations'] ?? [
            'er'        => '',
            'duration'  => '',
            'frequency' => '',
        ];
        $data['content_allowed']   = $data['content_allowed'] ?? [];
        $data['content_forbidden'] = $data['content_forbidden'] ?? [];
 
        if ( ! is_array( $data['checklist_moderator'] ) ) {
            $data['checklist_moderator'] = [];
        }
        if ( ! is_array( $data['content_allowed'] ) ) {
            $data['content_allowed'] = [];
        }
        if ( ! is_array( $data['content_forbidden'] ) ) {
            $data['content_forbidden'] = [];
        }
        if ( ! is_array( $data['metric_explanations'] ) ) {
            $data['metric_explanations'] = [ 'er' => '', 'duration' => '', 'frequency' => '' ];
        }
 
        return $data;
    }
}
