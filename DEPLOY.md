# Деплой PayWay Personal — инструкция для Cowork

## ВАЖНО: Как деплоить
Деплой происходит АВТОМАТИЧЕСКИ через GitHub Actions при пуше в ветку main.
НЕ нужно деплоить через терминал, FTP, SSH или любые другие ручные способы.
НЕ ИСПОЛЬЗУЙ терминал для git команд.

## Порядок деплоя

### Шаг 1: Внеси изменения в файлы
Редактируй PHP, Vue-компоненты, стили — что нужно.

### Шаг 2: Если менял файлы в src/ — собери фронтенд
npm run build
Это обновит assets/. Включи обновлённую папку assets/ в коммит.

### Шаг 3: Запуши изменения через GitHub API
Инструмент push_files из GitHub MCP-коннектора возвращает 403 для этого
приватного репозитория — у коннектора нет прав записи.

Вместо него используй GitHub Contents API напрямую через браузер.
Токен хранится в GitHub Secrets репозитория (GITHUB_TOKEN) и в переписке с Claude.
Claude умеет выполнять этот запрос автоматически через browser tool.

Алгоритм для Claude:
1. Получи SHA текущего файла через GET /repos/studiopioneer/payway-personal/contents/PATH
2. Закодируй новое содержимое в base64 (в sandbox: python3 -c "import base64; ...")
3. Отправь PUT /repos/studiopioneer/payway-personal/contents/PATH с полями:
   message, content (base64), sha, branch: main

### Шаг 4: Дождись автодеплоя
После пуша GitHub Actions автоматически загрузит файлы на тестовый сервер.
Статус: https://github.com/studiopioneer/payway-personal/actions
Время деплоя: ~2 минуты.

## Примечание по MCP
push_files из GitHub MCP → 403 Resource not accessible by integration.
Причина: OAuth-коннектор Cowork не имеет scope repo для приватных репо.
Редактировать токен коннектора в UI Cowork невозможно (только Connect/Disconnect).
Рабочий обходной путь: браузерный fetch с PAT через browser tool.
