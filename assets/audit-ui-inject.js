/**
 * PayWay Audit UI Injector v3
 * Читает данные из Pinia store и перестраивает DOM под прототип v2
 * Новая структура report: { summary, admission, demonetization, copyright }
 *   каждый блок: { risk: 'low'|'medium'|'high'|'ok', details: string }
 * Цветовая схема: красный акцент (#E8192C) + семантические цвета
 */
(function () {
  'use strict';

  // ── CSS (одноразовый инжект) ─────────────────────────────────────────────
  var CSS_ID = 'pw-aui-style-v3';
  if (!document.getElementById(CSS_ID)) {
    var style = document.createElement('style');
    style.id = CSS_ID;
    style.textContent = [
      '#pw-audit-inject{font-family:"Inter",system-ui,sans-serif;margin-bottom:16px}',
      '#pw-audit-inject *{box-sizing:border-box}',

      /* Verdict */
      '.pw-verdict{border-radius:10px;padding:16px 18px;display:flex;align-items:flex-start;gap:12px;margin-bottom:12px}',
      '.pw-verdict-accept{background:#f0fdf4;border:1px solid #bbf7d0}',
      '.pw-verdict-reject{background:#fef2f2;border:1px solid #fecaca}',
      '.pw-verdict-manual{background:#fffbeb;border:1px solid #fde68a}',
      '.pw-v-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}',
      '.pw-verdict-accept .pw-v-icon{background:#dcfce7}',
      '.pw-verdict-reject .pw-v-icon{background:#fee2e2}',
      '.pw-verdict-manual .pw-v-icon{background:#fef3c7}',
      '.pw-v-icon svg{width:16px;height:16px}',
      '.pw-verdict-accept .pw-v-icon svg{color:#16a34a}',
      '.pw-verdict-reject .pw-v-icon svg{color:#dc2626}',
      '.pw-verdict-manual .pw-v-icon svg{color:#d97706}',
      '.pw-v-title{font-size:14px;font-weight:600;margin-bottom:3px}',
      '.pw-verdict-accept .pw-v-title{color:#15803d}',
      '.pw-verdict-reject .pw-v-title{color:#b91c1c}',
      '.pw-verdict-manual .pw-v-title{color:#b45309}',
      '.pw-v-sub{font-size:12px;line-height:1.5}',
      '.pw-verdict-accept .pw-v-sub{color:#166534}',
      '.pw-verdict-reject .pw-v-sub{color:#991b1b}',
      '.pw-verdict-manual .pw-v-sub{color:#92400e}',

      /* Blocks row */
      '.pw-blocks-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:12px}',
      '.pw-bcard{background:#fff;border:1px solid #e8e8e8;border-radius:10px;padding:13px 15px}',
      '.pw-bcard-label{font-size:10px;font-weight:600;color:#bbb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}',
      '.pw-bcard-title{font-size:12px;font-weight:500;color:#1a1a1a;margin-bottom:8px}',
      '.pw-rbadge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:4px}',
      '.pw-rb-low,.pw-rb-ok{background:#f0fdf4;color:#16a34a}',
      '.pw-rb-medium,.pw-rb-med,.pw-rb-warn{background:#fffbeb;color:#d97706}',
      '.pw-rb-high,.pw-rb-fail{background:#fef2f2;color:#dc2626}',
      '.pw-rb-dot{width:6px;height:6px;border-radius:50%;background:currentColor}',

      /* Card */
      '.pw-card{background:#fff;border:1px solid #e8e8e8;border-radius:10px;margin-bottom:12px;overflow:hidden}',
      '.pw-card-header{padding:14px 18px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}',
      '.pw-card-title{font-size:13px;font-weight:500;color:#1a1a1a}',
      '.pw-card-body{padding:16px 18px}',

      /* Blur gate */
      '.pw-blur-wrap{position:relative;border-radius:8px;overflow:hidden;margin-bottom:14px}',
      '.pw-blur-content{background:#f9f9f9;padding:14px 16px;font-size:12px;line-height:1.6;color:#555;filter:blur(3.5px);user-select:none;min-height:80px}',
      '.pw-blur-gate{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:rgba(249,249,249,.75)}',
      '.pw-blur-gate-text{font-size:12px;color:#888;text-align:center}',
      '.pw-unlock-btn{height:30px;padding:0 14px;border-radius:6px;border:none;font-size:12px;font-weight:500;cursor:pointer;font-family:inherit;background:#E8192C;color:#fff}',
      '.pw-unlock-btn:hover{opacity:.88}',
      '.pw-unlock-btn:disabled{opacity:.5;cursor:default}',

      /* Tabs */
      '.pw-tab-row{display:flex;border-bottom:1px solid #f0f0f0}',
      '.pw-tab{font-size:12px;padding:9px 14px;cursor:pointer;color:#aaa;border-bottom:2px solid transparent;font-weight:500}',
      '.pw-tab.pw-tab-on{color:#E8192C;border-bottom-color:#E8192C}',
      '.pw-tab-panel{padding:16px 18px}',

      /* Criteria list */
      '.pw-cr-list{display:flex;flex-direction:column}',
      '.pw-cr-row{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #f5f5f5}',
      '.pw-cr-row:last-child{border-bottom:none}',
      '.pw-cr-dot{width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}',
      '.pw-cr-ok{background:#dcfce7}.pw-cr-ok svg{color:#16a34a}',
      '.pw-cr-fail{background:#fee2e2}.pw-cr-fail svg{color:#dc2626}',
      '.pw-cr-warn{background:#fef3c7}.pw-cr-warn svg{color:#d97706}',
      '.pw-cr-dot svg{width:9px;height:9px}',
      '.pw-cr-name{font-size:12px;font-weight:500;color:#1a1a1a}',
      '.pw-cr-desc{font-size:11px;color:#aaa;margin-top:1px;line-height:1.4}',

      /* Risk rows */
      '.pw-risk-section-title{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#bbb;padding:10px 0 6px}',
      '.pw-risk-row{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #f7f7f7}',
      '.pw-risk-row:last-child{border-bottom:none}',
      '.pw-rl-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:4px}',
      '.pw-rl-high{background:#dc2626}.pw-rl-med{background:#d97706}.pw-rl-low{background:#16a34a}',
      '.pw-risk-title{font-size:12px;font-weight:500;color:#1a1a1a;margin-bottom:2px}',
      '.pw-risk-desc{font-size:11px;color:#888;line-height:1.5}',
      '.pw-risk-rec{font-size:11px;color:#555;margin-top:5px;padding:5px 9px;background:#f9f9f9;border-radius:5px;border-left:2px solid #e8e8e8;line-height:1.5}',

      /* Reused box */
      '.pw-reused-box{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:13px 15px;margin-bottom:10px}',
      '.pw-reused-title{font-size:12px;font-weight:600;color:#991b1b;margin-bottom:8px;display:flex;align-items:center;gap:6px}',
      '.pw-signal-row{display:flex;align-items:flex-start;gap:8px;padding:5px 0;border-bottom:1px solid rgba(220,38,38,.1)}',
      '.pw-signal-row:last-child{border-bottom:none}',
      '.pw-sig-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px}',
      '.pw-sig-high{background:#dc2626}.pw-sig-med{background:#d97706}.pw-sig-low{background:#16a34a}',
      '.pw-sig-title{font-size:11px;font-weight:500;color:#7f1d1d}',
      '.pw-sig-val{font-size:11px;color:#991b1b;margin-top:1px}',
      '.pw-sig-rec{font-size:11px;color:#b91c1c;font-style:italic;margin-top:2px}',

      /* Flag note */
      '.pw-flag-note{font-size:12px;color:#888;background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:10px 13px;line-height:1.55;margin:0 16px 16px}',
      '.pw-flag-note strong{color:#92400e}',

      /* Action row */
      '.pw-action-row{display:flex;gap:10px;flex-wrap:wrap;padding:0 16px 16px}',
      '.pw-btn{height:38px;padding:0 16px;border-radius:8px;border:none;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit}',
      '.pw-btn-red{background:#E8192C;color:#fff}.pw-btn-red:hover{opacity:.88}',
      '.pw-btn-ghost{background:#fff;border:1px solid #e8e8e8;color:#555}.pw-btn-ghost:hover{background:#fafafa}',
    ].join('');
    document.head.appendChild(style);
  }

  // ── SVG иконки ──────────────────────────────────────────────────────────
  var ICONS = {
    check:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    warn:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    check_v: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  };

  // ── Вспомогательные функции ─────────────────────────────────────────────
  function h(tag, attrs, inner) {
    var el = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) { el.setAttribute(k, attrs[k]); });
    if (inner !== undefined) el.innerHTML = inner;
    return el;
  }

  function riskLabel(risk) {
    return ({ low: 'Низкий', medium: 'Средний', high: 'Высокий', ok: 'Пройден', warn: 'Внимание', fail: 'Провал' })[risk] || (risk || 'Нет данных');
  }

  function riskCls(risk) {
    return 'pw-rbadge pw-rb-' + (risk || 'low');
  }

  function dotCls(level) {
    return 'pw-rl-dot ' + ({ high: 'pw-rl-high', medium: 'pw-rl-med', low: 'pw-rl-low', ok: 'pw-rl-low', warn: 'pw-rl-med', fail: 'pw-rl-high' }[level] || 'pw-rl-low');
  }

  function badge(risk) {
    return '<span class="' + riskCls(risk) + '"><span class="pw-rb-dot"></span>' + riskLabel(risk) + '</span>';
  }

  // ── Pinia store ─────────────────────────────────────────────────────────
  function getStore() {
    try {
      var el = document.querySelector('[data-v-app]');
      if (!el || !el.__vue_app__) return null;
      var pinia = el.__vue_app__.config.globalProperties.$pinia;
      if (!pinia || !pinia._s) return null;
      return pinia._s.get('audit');
    } catch (e) { return null; }
  }

  // ── Вердикт: вывести из рисков блоков �сли явно не задан ───────────────
  function deriveVerdict(report) {
    if (report.verdict) return report.verdict;
    var b1 = (report.admission      && report.admission.risk)      || 'ok';
    var b2 = (report.demonetization && report.demonetization.risk) || 'low';
    var b3 = (report.copyright      && report.copyright.risk)      || 'low';
    if (b1 === 'high' || b1 === 'fail') return 'reject';
    if (b2 === 'high' || b3 === 'high' || b2 === 'medium' || b3 === 'medium') return 'manual';
    return 'accept';
  }

  // ── Verdict Banner ───────────────────────────────────────────────────────
  function buildVerdictBanner(report) {
    var v = deriveVerdict(report);
    var reason = report.verdict_reason || report.summary || '';
    var cfg = {
      accept: { cls: 'pw-verdict-accept', icon: ICONS.check_v, title: 'Канал соответствует требованиям монетизации' },
      reject: { cls: 'pw-verdict-reject', icon: ICONS.x,       title: 'Канал не соответствует требованиям' },
      manual: { cls: 'pw-verdict-manual', icon: ICONS.warn,    title: 'Требует ручной проверки' },
    }[v] || { cls: 'pw-verdict-manual', icon: ICONS.warn, title: 'Требует ручной проверки' };

    var el   = h('div', { class: 'pw-verdict ' + cfg.cls });
    var icon = h('div', { class: 'pw-v-icon' }, cfg.icon);
    var body = h('div');
    body.appendChild(h('div', { class: 'pw-v-title' }, cfg.title));
    if (reason) body.appendChild(h('div', { class: 'pw-v-sub' }, reason));
    el.appendChild(icon);
    el.appendChild(body);
    return el;
  }

  // ── 3 карточки блоков ────────────────────────────────────────────────────
  function buildBlocksRow(report) {
    var row = h('div', { class: 'pw-blocks-row' });
    [
      { label: 'Блок 1', title: 'Допуск к монетизации',    risk: (report.admission      && report.admission.risk)      || 'ok'  },
      { label: 'Блок 2', title: 'Риск демонетизации',      risk: (report.demonetization && report.demonetization.risk) || 'low' },
      { label: 'Блок 3', title: 'Авторские права / страйки', risk: (report.copyright      && report.copyright.risk)      || 'low' },
    ].forEach(function (b) {
      var card = h('div', { class: 'pw-bcard' });
      card.appendChild(h('div', { class: 'pw-bcard-label' }, b.label));
      card.appendChild(h('div', { class: 'pw-bcard-title' }, b.title));
      card.innerHTML += badge(b.risk);
      row.appendChild(card);
    });
    return row;
  }

  // ── Preview-карточка (не оплачено) ───────────────────────────────────────
  function buildPreviewCard(report, store) {
    var card = h('div', { class: 'pw-card' });

    var hdr = h('div', { class: 'pw-card-header' });
    hdr.appendChild(h('div', { class: 'pw-card-title' }, 'Полный отчёт с рекомендациями'));
    hdr.innerHTML += '<div style="font-size:12px;color:#aaa">Стоимость: <b style="color:#E8192C">$2.00</b></div>';
    card.appendChild(hdr);

    var body = h('div', { class: 'pw-card-body' });

    // Preview text (blurred) — показываем детали блоков
    var previewText = [
      (report.admission      && report.admission.details),
      (report.demonetization && report.demonetization.details),
      (report.copyright      && report.copyright.details),
    ].filter(Boolean).join(' ');
    if (!previewText) {
      previewText = 'Детальный анализ допуска к монетизации, рисков демонетизации и авторских прав. Сигналы, критерии и пошаговые рекомендации автору канала...';
    }

    var wrap    = h('div', { class: 'pw-blur-wrap' });
    var content = h('div', { class: 'pw-blur-content' }, previewText);
    wrap.appendChild(content);

    var gate     = h('div', { class: 'pw-blur-gate' });
    var gateText = h('div', { class: 'pw-blur-gate-text' }, 'Детальный разбор и рекомендации скрыты');

    var unlockInfo = (report.unlock_info) || (store && store.unlockInfo) || {};
    var balance    = Number(unlockInfo.balance || 0);
    var btnText    = 'Открыть полный отчёт — $2.00';
    if (balance > 0) {
      btnText = 'Открыть полный отчёт — $2.00 (баланс: $' + balance.toFixed(2) + ')';
    } else if (unlockInfo.credit_available) {
      btnText = 'Получить отчёт (бесплатно)';
    }

    var btn = h('button', { class: 'pw-unlock-btn' }, btnText);
    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = 'Оплата...';
      var st = getStore();
      if (st && typeof st.unlockReport === 'function') {
        var id = (report.id != null ? report.id : null) || (st.auditId != null ? st.auditId : null);
        st.unlockReport(id).then(function () {
          var s = getStore();
          if (s && s.report) renderReport(s);
        }).catch(function () {
          btn.disabled = false;
          btn.textContent = btnText;
        });
      } else {
        btn.disabled = false;
        btn.textContent = btnText;
      }
    });

    gate.appendChild(gateText);
    gate.appendChild(btn);
    wrap.appendChild(gate);
    body.appendChild(wrap);
    body.appendChild(h('div', { style: 'font-size:11px;color:#ccc;text-align:center' }, 'Детальный разбор каждого сигнала · Конкретные рекомендации автору'));
    card.appendChild(body);
    return card;
  }

  // ── Полный отчёт (оплачен) ───────────────────────────────────────────────
  function buildFullReport(report) {
    var wrap = h('div', { class: 'pw-card' });

    var tabDefs = [
      { label: 'Блок 1 · Допуск',        data: report.admission      || {} },
      { label: 'Блок 2 · Демонетизация', data: report.demonetization || {} },
      { label: 'Блок 3 · Страйки',       data: report.copyright      || {} },
    ];
    var panelTitles = ['Обязательные критерии', 'Риски демонетизации', 'Риски авторских прав'];

    // Tab row
    var tabRow = h('div', { class: 'pw-tab-row' });
    var panels = [];

    tabDefs.forEach(function (td, i) {
      var tab = h('div', { class: 'pw-tab' + (i === 0 ? ' pw-tab-on' : '') }, td.label);
      tab.addEventListener('click', function () {
        wrap.querySelectorAll('.pw-tab').forEach(function (t) { t.classList.remove('pw-tab-on'); });
        tab.classList.add('pw-tab-on');
        panels.forEach(function (p, j) { p.style.display = i === j ? '' : 'none'; });
      });
      tabRow.appendChild(tab);
    });
    wrap.appendChild(tabRow);

    tabDefs.forEach(function (td, i) {
      var panel = h('div', { class: 'pw-tab-panel', style: i === 0 ? '' : 'display:none' });
      var data  = td.data;

      // Subheader with risk badge
      var phdr = h('div', { style: 'display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:6px' });
      phdr.appendChild(h('div', { style: 'font-size:13px;font-weight:500;color:#1a1a1a' }, panelTitles[i]));
      phdr.innerHTML += badge(data.risk || 'low');
      panel.appendChild(phdr);

      // Content: criteria array, signals array, or plain text
      if (Array.isArray(data.criteria) && data.criteria.length) {
        var crList = h('div', { class: 'pw-cr-list' });
        data.criteria.forEach(function (c) { crList.appendChild(buildCriteriaRow(c)); });
        panel.appendChild(crList);
      } else if (Array.isArray(data.signals) && data.signals.length) {
        var highSigs = data.signals.filter(function (s) { return s.level === 'high'; });
        if (highSigs.length >= 2) {
          panel.appendChild(buildReusedBox(data.signals));
        } else {
          var sect = h('div');
          data.signals.forEach(function (sig) { sect.appendChild(buildRiskRow(sig)); });
          panel.appendChild(sect);
        }
      } else if (data.details) {
        panel.appendChild(h('div', { style: 'font-size:12px;line-height:1.7;color:#555' }, data.details));
      } else {
        panel.appendChild(h('p', { style: 'font-size:12px;color:#aaa' }, 'Данные блока не обнаружены'));
      }

      panels.push(panel);
      wrap.appendChild(panel);
    });

    // Summary for moderator
    if (report.summary) {
      var note = h('div', { class: 'pw-flag-note' });
      note.innerHTML = '<strong>Итог для модератора:</strong> ' + report.summary;
      wrap.appendChild(note);
    }

    // Action buttons
    var actRow = h('div', { class: 'pw-action-row' });
    var btnNew = h('button', { class: 'pw-btn pw-btn-ghost' }, 'Проверить другой канал');
    btnNew.addEventListener('click', function () {
      removeInject();
      var st = getStore();
      if (st) { st.status = null; st.report = null; st.auditId = null; }
    });
    actRow.appendChild(btnNew);
    wrap.appendChild(actRow);

    return wrap;
  }

  // ── Строка критерия (Блок 1) ─────────────────────────────────────────────
  function buildCriteriaRow(c) {
    var status  = c.status || 'ok';
    var iconMap = { ok: ICONS.check, fail: ICONS.x, warn: ICONS.warn };
    var row = h('div', { class: 'pw-cr-row' });
    var dot = h('div', { class: 'pw-cr-dot pw-cr-' + status }, iconMap[status] || ICONS.check);
    var info = h('div');
    info.appendChild(h('div', { class: 'pw-cr-name' }, c.name || ''));
    if (c.detail) info.appendChild(h('div', { class: 'pw-cr-desc' }, c.detail));
    row.appendChild(dot);
    row.appendChild(info);
    return row;
  }

  // ── Блок reused content ──────────────────────────────────────────────────
  function buildReusedBox(signals) {
    var box = h('div', { class: 'pw-reused-box' });
    var highCount = signals.filter(function (s) { return s.level === 'high'; }).length;
    var title = h('div', { class: 'pw-reused-title' });
    title.innerHTML = ICONS.warn + ' Reused / Mass-produced контент — ' + signals.length +
      ' сигнал' + (signals.length > 1 ? 'а' : '') + ' уровня ' +
      (highCount >= 2 ? 'Высокого' : 'Среднего');
    box.appendChild(title);
    signals.forEach(function (sig) {
      var row  = h('div', { class: 'pw-signal-row' });
      var lmap = { high: 'pw-sig-high', medium: 'pw-sig-med', low: 'pw-sig-low' };
      var dot  = h('div', { class: 'pw-sig-dot ' + (lmap[sig.level] || 'pw-sig-med') });
      var info = h('div');
      info.appendChild(h('div', { class: 'pw-sig-title' }, sig.title || ''));
      info.appendChild(h('div', { class: 'pw-sig-val' },   sig.detail || sig.description || ''));
      if (sig.recommendation) info.appendChild(h('div', { class: 'pw-sig-rec' }, sig.recommendation));
      row.appendChild(dot);
      row.appendChild(info);
      box.appendChild(row);
    });
    return box;
  }

  // ── Строка риска (Блоки 2/3) ─────────────────────────────────────────────
  function buildRiskRow(sig) {
    var row  = h('div', { class: 'pw-risk-row' });
    var dot  = h('div', { class: dotCls(sig.level) });
    var info = h('div', { style: 'flex:1' });
    info.appendChild(h('div', { class: 'pw-risk-title' }, sig.title || ''));
    if (sig.description) info.appendChild(h('div', { class: 'pw-risk-desc' }, sig.description));
    if (sig.recommendation) info.appendChild(h('div', { class: 'pw-risk-rec' }, sig.recommendation));
    row.appendChild(dot);
    row.appendChild(info);
    return row;
  }

  // ── Главная функция рендера ──────────────────────────────────────────────
  function removeInject() {
    var el = document.getElementById('pw-audit-inject');
    if (el) el.remove();
    // Возвращаем Vue-элементы
    var ar = document.querySelector('.audit-result');
    if (ar) ar.style.display = '';
    var ub = document.querySelector('.audit-unlock-button');
    if (ub) ub.style.display = '';
  }

  function renderReport(store) {
    var report = store.report;
    if (!report) return;

    var auditResult = document.querySelector('.audit-result');
    if (!auditResult) return; // Vue ещё не отрисовал блок результатов — повторим позже

    var container = auditResult.parentElement;
    if (!container) return;

    // Получаем или создаём контейнер инжекта
    var inject = document.getElementById('pw-audit-inject');
    if (!inject) {
      inject = h('div', { id: 'pw-audit-inject' });
      container.insertBefore(inject, auditResult);
    }

    inject.innerHTML = '';

    // 1. Вердикт
    inject.appendChild(buildVerdictBanner(report));

    // 2. Три блока
    inject.appendChild(buildBlocksRow(report));

    // 3. Основной контент
    var isPaid = store.isPaid || (report && report.is_paid);
    inject.appendChild(isPaid ? buildFullReport(report) : buildPreviewCard(report, store));

    // Скрываем оригинальный Vue-секции
    auditResult.style.display = 'none';
    var unlockDiv = document.querySelector('.audit-unlock-button');
    if (unlockDiv) unlockDiv.style.display = 'none';
  }

  // ── Цикл опроса store ────────────────────────────────────────────────────
  function tryRender(attempts) {
    if (attempts <= 0) return;
    var store = getStore();
    if (!store) {
      setTimeout(function () { tryRender(attempts - 1); }, 400);
      return;
    }

    // Первый рендер, если уже done (например, навигация ?id=N)
    if (store.status === 'done' && store.report) {
      renderReport(store);
    }

    // Ключ изменения: auditId + isPaid + status (не report.id, которого может не быть)
    var lastKey = (store.auditId || '') + '/' + (store.isPaid ? '1' : '0') + '/' + (store.status || '');

    setInterval(function () {
      var s = getStore();
      if (!s) return;

      var currKey = (s.auditId || '') + '/' + (s.isPaid ? '1' : '0') + '/' + (s.status || '');

      if (currKey !== lastKey) {
        lastKey = currKey;
        if (s.status === 'done' && s.report) {
          renderReport(s);
        } else {
          // Вернулись к форме / анализу — убираем инжект
          removeInject();
        }
      }

      // Дополнительный guard: если инжект исчез (Vue перерисовал), вернуть его
      if (!document.getElementById('pw-audit-inject') && s.status === 'done' && s.report) {
        renderReport(s);
      }
    }, 800);
  }

  // ── Старт ────────────────────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(function () { tryRender(30); }, 600);
    });
  } else {
    setTimeout(function () { tryRender(30); }, 600);
  }

})();