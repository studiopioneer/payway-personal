Payway Personal — Рабочие заметки
Проект

Репо: studiopioneer/payway-personal (ветка main, также ветка v8.0)
WordPress-плагин на сервере hikartveli.com
Активная папка плагина: payway-personal-backup-working-v8.2 (НЕ payway-personal-7.0!)
GitHub Actions авто-деплой по FTPS при пуше в main (.github/workflows/deploy-test.yml)
Backup-ветка: backup-working-v7.0
Текущая версия на сервере: 8.0 (JS ver=8.3)

Workflow: как мы пушим и деплоим

Claude готовит изменённые файлы и отдаёт их через .txt ссылку (Cowork не открывает .js в сайдбаре)
Пользователь копирует код в файл, коммитит и пушит в GitHub через PowerShell
GitHub Actions автоматически деплоит на сервер по FTPS
После пуша JS-файлов — поднять ?ver=X.X в payway-personal.php для сброса кеша
JS подключается через PHP: echo '<script src="' . esc_url( $url ) . '"></script>'; в wp_footer с проверкой strpos( $_SERVER['REQUEST_URI'], '/audit' )

Важно: Claude НЕ пушит напрямую в GitHub. Только готовит файлы → пользователь пушит.
PowerShell: Не поддерживает && — команды нужно давать по одной.
git push rejected: Часто бывает, решается через git pull origin main --rebase потом git push.
FTP/FTPS деплой (настроено апрель 2026)

FTP_USER: hikartveli@hikartveli.com (новый аккаунт, home dir → папка плагина)
FTP_PASSWORD: пароль cPanel
REMOTE_HOST: хост FTP сервера
FTP_PATH: путь к папке плагина от FTP root
protocol: ftps — обязательно! plain ftp даёт 421, ftps работает
Старый аккаунт deploy@hikartveli.com указывал на несуществующую папку personal-7.0 → 421 ошибка

Структура файлов (ловушки)

В репо есть двойные папки: includes/class-audit-rest.php (загружается WordPress) и includes/includes/class-audit-rest.php (НЕ загружается). Всегда проверять правильный путь через git diff --name-only.
assets/audit-ui-inject.js — основной JS inject, всегда в корне assets/
includes/class-audit-credit.php — класс бесплатных отчётов
includes/class-audit-rest.php — REST API (start_audit, unlock_report, get_audit, history)
admin/pages/class-donations-page.php — вкладка Донаты в WP Admin
admin/pages/list-tables/class-donations-list-table.php — таблица донатов

PHP namespace ловушка (критично!)

namespace Payway\Pages; ОБЯЗАТЕЛЬНО должен быть ПЕРВЫМ после <?php — до любых if (!defined('ABSPATH')) проверок
Нарушение → PHP Fatal: "Namespace declaration statement has to be the very first statement"
display_tablenav() в WP_List_Table — public, child class тоже должен объявлять public (не protected)
DonationsPage::init() вызывается изнутри хука admin_menu — нельзя снова делать add_action('admin_menu', ...), нужно напрямую: ( new static() )->register_page()

Технические детали

audit-ui-inject.js — standalone JS (IIFE, var, h() хелпер), не часть Vue SPA билда
Pinia store: document.querySelector('[data-v-app]').__vue_app__.config.globalProperties.$pinia._s.get('audit')
КРИТИЧНО: Pinia store 'audit' существует ТОЛЬКО когда смонтирован AuditView компонент. На /audit-history есть только ['user','toast'] — store 'audit' = undefined!
КРИТИЧНО: store.report ВСЕГДА null — данные нужно загружать через API (fetchAuditFull)
Store status flow при аудите: pending → done (НЕ processing!)
REST API: /wp-json/payway/v1/audit/{id}/status
Прелоадер вставляется в [data-v-app] .col:not(.col-fixed) > div (.audit-result не существует при pending)
Rate limit для админов снят (обёрнут в if ( ! current_user_can('manage_options') ))
Vue AuditView крашится с Cannot read properties of null (reading 'summary') — но наш inject перекрывает Vue UI

SPA-навигация: решённые и оставшиеся проблемы
Решено (19 апреля 2026):

URL change detection — вынесено ДО проверки store (иначе if (!s) return блокировал всё на страницах без audit store)
Лендинг на /audit-history — isAuditFormPage() ограничивает лендинг только страницей /audit/ без ?id=
Старый отчёт на /audit/ — _wasProcessing флаг: рендерим отчёт на /audit/ без ?id= ТОЛЬКО если аудит запущен на текущей странице (pending→done). Без флага — показываем форму/лендинг
Vue siblings скрытые после навигации — removeInject() восстанавливает display всех siblings
Старый Vue прелоадер поверх нашего — скрываем ВСЕ siblings при показе прелоадера
Контент прилип к верху — CSS [data-v-app] .col:not(.col-fixed) > div{padding-top:24px}
Nonce недоступен при SPA-навигации — перехват fetch() и XMLHttpRequest.setRequestHeader() для захвата X-WP-Nonce из Vue-запросов. Отложенный retry если nonce ещё пуст

Нестабильность SPA-навигации (НЕРЕШЕНО):

Страницы аудита загружаются нестабильно при SPA-переходах: то нормально, то без формы, то старый контент
Жёсткая перезагрузка (Ctrl+F5) всегда помогает
Корневая причина: наш inject — внешний IIFE, работающий поверх Vue SPA. Vue Router меняет DOM, пересоздаёт компоненты, store может создаваться/уничтожаться. Наш setInterval (800ms) пытается следить за изменениями, но timing может не совпадать
Возможное решение: переписать inject на Vue plugin/mixin, который интегрируется в жизненный цикл Vue, вместо внешнего setInterval

Ключевые хелперы в JS:

isAuditFormPage() — /audit/ без ?id=, без -history
isAuditReportPage() — /audit/?id=X
isAuditPage() — любой /audit (не /audit-history)
_wasProcessing — true когда видели pending/processing, сбрасывается при route change
canRenderReport = isAuditReportPage() || _wasProcessing
getBestNonce() — ищет nonce из перехваченных запросов, paywayAuditCfg, wpApiSettings

Завершённые спринты

Спринты 1–5: базовый функционал аудита
Спринт 6.4 (v4.6-hotfix): guard в buildChannelCard, ER hint валидация, niche-based contentAllowed fallback
Спринт v4.7 (preloader): информативный прелоадер с чеклистом шагов + фикс unlock кнопки
Спринт v4.8 (19 апреля 2026): Новая логика оплаты + лендинг + SPA-навигация
Спринт v4.9 (24 апреля 2026): Auth fix, только бесплатные кредиты, donate блок, вкладка Донаты в WP Admin + фикс FTPS деплоя

Спринт v4.9 — СТАТУС (24 апреля 2026) ✅ ЗАДЕПЛОЕН
Что ЗАДЕПЛОЕНО и РАБОТАЕТ:

class-audit-credit.php — 3 отчёта на аккаунт, 1 в день, check()/consume()/get_status()
unlock_report() — приоритет баланс → бесплатные → блок, возвращает credit_status
unlock_info в get_audit() — передаёт credit_available и credit_status
JS кнопка unlock — текст с оставшимися отчётами, оплата работает, полный отчёт открывается
Лендинговый блок — hero + 3 карточки + tech chips на /audit/
Прелоадер — анимированный чеклист шагов при pending
SPA-навигация — базовая: URL detection, removeInject, isAuditPage guards
Nonce перехват — из fetch/XHR для SPA-навигации
CSS padding-top — отступ от верхнего края
Donate блок — buildDonateBlock() в JS
Таблица wp_payway_donations + REST endpoint /donate
Вкладка «Донаты» в WP Admin → PW Кабинет (DonationsPage + DonationsListTable)
GitHub Actions FTPS деплой — работает ✅

Что НУЖНО ДОДЕЛАТЬ:

Стабильность SPA-навигации — нестабильная загрузка при переходах
SEO мета-теги для /audit/ — Title и Description
