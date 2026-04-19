/**
 * withdrawal-tariff-inject.js  v1.1
 *
 * Для старых пользователей (тариф 10%):
 *  - заменяет "11%" → "10%" в лейблах
 *  - пересчитывает сумму комиссии и "Вы получите" в калькуляторе
 *
 * Файл: /wp-content/plugins/payway-personal-7.0/assets/withdrawal-tariff-inject.js
 */
 
(function () {
    'use strict';
 
    var cfg          = window.paywayWithdrawalCfg || {};
    var cryptoTariff = parseInt(cfg.cryptoTariff, 10);
 
    // Тариф 11 — ничего не меняем
    if (!cryptoTariff || cryptoTariff === 11) return;
 
    // ── Получить сумму из первого незаблокированного input'а с числом > 0 ────
    function getInputAmount() {
        var inputs = document.querySelectorAll('input');
        for (var i = 0; i < inputs.length; i++) {
            var v = parseFloat(inputs[i].value);
            if (!isNaN(v) && v > 0) return v;
        }
        return 0;
    }
 
    // ── Основной патч ─────────────────────────────────────────────────────────
    function patchAll() {
        var amount = getInputAmount();
 
        // Считаем значения для 11% и 10%
        var comm11 = amount > 0 ? parseFloat((amount * 0.11).toFixed(2)) : null;
        var comm10 = amount > 0 ? parseFloat((amount * 0.10).toFixed(2)) : null;
        var rec11  = amount > 0 ? parseFloat((amount - comm11).toFixed(2)) : null;
        var rec10  = amount > 0 ? parseFloat((amount - comm10).toFixed(2)) : null;
 
        // Обходим все текстовые узлы
        var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
        var node;
        while ((node = walker.nextNode())) {
            if (!node.nodeValue || !node.parentElement) continue;
            var parent = node.parentElement;
 
            // Пропускаем input/textarea
            if (parent.tagName === 'INPUT' || parent.tagName === 'TEXTAREA') continue;
 
            var text = node.nodeValue;
 
            // 1. Лейбл "Комиссия (11%)" → "Комиссия (10%)"
            if (text.indexOf('11%') !== -1) {
                node.nodeValue = text.replace(/11%/g, '10%');
                text = node.nodeValue; // обновляем для дальнейших замен
            }
 
            if (amount <= 0 || comm10 === comm11) continue;
 
            // Определяем контекст: смотрим на текст родителя и его соседей
            var grandparent  = parent.parentElement || parent;
            var contextText  = grandparent.textContent || '';
            var isCommRow    = contextText.indexOf('Комисс') !== -1;
            var isReceiveRow = contextText.indexOf('получит') !== -1;
 
            // 2. Сумма комиссии: заменяем comm11 → comm10
            if (isCommRow && comm11 !== null) {
                var comm11Int = Math.round(comm11).toString();
                var comm10Int = Math.round(comm10).toString();
 
                // Ищем паттерн: необязательный знак минус + пробел + число
                var re = new RegExp('([-\u2212]\\s*)?' + escapeRegex(comm11Int), 'g');
                var patched = text.replace(re, function (match) {
                    return match.replace(comm11Int, comm10Int);
                });
                if (patched !== text) {
                    node.nodeValue = patched;
                    text = patched;
                }
            }
 
            // 3. "Вы получите": заменяем rec11 → rec10
            if (isReceiveRow && rec11 !== null) {
                var rec11Int = Math.round(rec11).toString();
                var rec10Int = Math.round(rec10).toString();
 
                var re = new RegExp('\\b' + escapeRegex(rec11Int) + '\\b', 'g');
                var patched = text.replace(re, rec10Int);
                if (patched !== text) {
                    node.nodeValue = patched;
                }
            }
        }
    }
 
    // Экранируем спецсимволы для RegExp
    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
 
    // ── MutationObserver: следим за обновлениями Vue SPA ─────────────────────
    var observer = new MutationObserver(function () {
        patchAll();
    });
 
    function start() {
        patchAll();
        observer.observe(document.body, { childList: true, subtree: true, characterData: true });
    }
 
    if (document.body) {
        start();
    } else {
        document.addEventListener('DOMContentLoaded', start);
    }
 
    // Страховочные проходы для медленного рендера
    setTimeout(patchAll, 500);
    setTimeout(patchAll, 1500);
    setTimeout(patchAll, 3000);
 
})();