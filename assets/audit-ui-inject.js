/**
 * PayWay Audit UI Injector v4
 * Читает данные из Pinia store и перестраивает DOM под прототип v2
 *
 * store.report  : { verdict, verdict_reason, summary, admission, demonetization, copyright }
 *   admission/demonetization/copyright: { risk, details }
 * store.preview : { subscriber_count, view_count, video_count, age_months,
 *                   videos_per_month, avg_er, country, topic_categories,
 *                   php_signals, php_signals_count, block1_criteria }
 * store.full    : { block1_criteria, block2_signals, block3_signals, php_signals,
 *                   summary_for_moderator, recommendations_for_user, channel_metrics }
 */
(function () {
  'use strict';

  // ── CSS (одноразовый инжект) ─────────────────────────────────────────────
  var CSS_ID = 'pw-aui-style-v4';
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

      /* Metrics grid */
      '.pw-metrics-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:14px}',
      '.pw-metric-item{background:#f9f9f9;border-radius:7px;padding:10px 12px}',
      '.pw-metric-label{font-size:10px;color:#bbb;margin-bottom:3px;font-weight:500;text-transform:uppercase;letter-spacing:.03em}',
      '.pw-metric-value{font-size:13px;font-weight:500;color:#1a1a1a}',
      '.pw-metric-value.pw-mv-warn{color:#dc2626}',

      /* Blur gate */
      '.pw-blur-wrap{position:relative;border-radius:8px;overflow:hidden;margin-bottom:14px}',
      '.pw-blur-content{background:#f9f9f9;padding:14px 16px;font-size:12px;line-height:1.6;color:#555;filter:blur(3.5px);user-select:none;min-height:80px}',
      '.pw-blur-gate{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:rgba(249,249,249,.75)}',
      '.pw-blur-gate-text{font-size:12px;color:#888;text-align:center}',
      '.pw-unlock-btn{height:30px;padding:0 14px;border-radius:6px;border:none;font-size:12px;font-weight:500;cursor:pointer;font-family:inherit;background:#E8192C;color:#fff}',
      '.pw-unlock-btn:hover{opacity:.88}',
      '.pw-unlock-btn:disabled{opacity:.5;cursor:default}',
      '.pw-unlock-error{font-size:11px;color:#dc2626;text-align:center}',

      /* Tabs */
      '.pw-tab-row{display:flex;border-bottom:1px solid #f0f0f0}',
      '.pw-tab{font-size:12px;padding:9px 14px;cursor:pointer;color:#aaa;border-bottom:2px solid transparent;font-weight:500;white-space:nowrap}',
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
      '.pw-risk-section-title{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#bbb;padding:10px 0 6px;display:flex;align-items:center;gap:6px}',
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
      '.pw-flag-note{font-size:12px;color:#888;background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:10px 13px;line-height:1.55;margin:0 16px 14px}',
      '.pw-flag-note strong{color:#92400e}',

      /* Recommendations */
      '.pw-recs-section{padding:0 18px 16px}',
      '.pw-recs-title{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#bbb;padding:4px 0 10px}',
      '.pw-rec-item{display:flex;align-items:flex-start;gap:9px;padding:6px 0;border-bottom:1px solid #f5f5f5}',
      '.pw-rec-item:last-child{border-bottom:none}',
      '.pw-rec-num{width:18px;height:18px;border-radius:50%;background:#f0f0f0;font-size:9px;font-weight:700;color:#888;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}',
      '.pw-rec-text{font-size:11px;color:#555;line-height:1.5}',

      /* Action row */
      '.pw-action-row{display:flex;gap:10px;flex-wrap:wrap;padding:0 16px 16px}',
      '.pw-btn{height:38px;padding:0 16px;border-radius:8px;border:none;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit}',
      '.pw-btn-red{background:#E8192C;color:#fff}.pw-btn-red:hover{opacity:.88}',
      '.pw-btn-ghost{background:#fff;border:1px solid #e8e8e8;color:#555}.pw-btn-ghost:hover{background:#fafafa}',

      /* ── Input page enhancements ── */
      '@keyframes pw-spin{to{transform:rotate(360deg)}}',
      '#pw-check-section{margin-top:14px;background:#fff;border-radius:12px;border:1px solid #e8e8e8;overflow:hidden}',
      '.pw-check-hdr{padding:13px 18px;border-bottom:1px solid #f0f0f0;font-size:13px;font-weight:500;color:#1a1a1a;display:flex;align-items:center;gap:8px}',
      '.pw-check-hdr-icon{width:7px;height:7px;border-radius:50%;background:#E8192C;flex-shrink:0;animation:pw-spin 1s linear infinite}',
      '.pw-check-grid{display:grid;grid-template-columns:repeat(3,1fr)}',
      '.pw-check-block{padding:13px 15px;border-left:1px solid #f0f0f0;transition:background .4s}',
      '.pw-check-block:first-child{border-left:none}',
      '.pw-check-block.pw-cb-running{background:#fffafa}',
      '.pw-check-block.pw-cb-done-ok{background:#f9fef9}',
      '.pw-check-block.pw-cb-done-high{background:#fff9f9}',
      '.pw-check-block.pw-cb-done-med{background:#fffdf4}',
      '.pw-cb-num{font-size:10px;font-weight:600;color:#bbb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}',
      '.pw-cb-title{font-size:12px;font-weight:500;color:#1a1a1a;margin-bottom:5px}',
      '.pw-cb-desc{font-size:11px;color:#aaa;line-height:1.45}',
      '.pw-cb-spin-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}',
      '.pw-cb-spinner{width:13px;height:13px;border:2px solid #f0f0f0;border-top-color:#E8192C;border-radius:50%;animation:pw-spin .75s linear infinite;flex-shrink:0}',
      '.pw-cb-running-lbl{font-size:11px;font-weight:600;color:#E8192C}',
      '.pw-cb-done-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}',
      '.pw-cb-done-icon{width:15px;height:15px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}',
      '.pw-cb-done-icon svg{width:8px;height:8px}',
      '.pw-cb-done-lbl{font-size:11px;font-weight:600}',
      '#pw-balance-note{font-size:12px;color:#aaa;margin-top:10px;padding:0 2px;line-height:1.5}',
      '#pw-balance-note b{font-weight:500;color:#E8192C}',
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

  fdius:50%;flex-shrink:0;margin-top:4px}',
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
      '.pw-flag-note{font-size:12px;color:#888;background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:10px 13px;line-height:1.55;margin:0 16px 14px}',
      '.pw-flag-note strong{color:#92400e}',

      /* Recommendations */
      '.pw-recs-section{padding:0 18px 16px}',
      '.pw-recs-title{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#bbb;padding:4px 0 10px}',
      '.pw-rec-item{display:flex;align-items:flex-start;gap:9px;padding:6px 0;border-bottom:1px solid #f5f5f5}',
      '.pw-rec-item:last-child{border-bottom:none}',
      '.pw-rec-num{width:18px;height:18px;border-radius:50%;background:#f0f0f0;font-size:9px;font-weight:700;color:#888;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}',
      '.pw-rec-text{font-size:11px;color:#555;line-height:1.5}',

      /* Action row */
      '.pw-action-row{display:flex;gap:10px;flex-wrap:wrap;padding:0 16px 16px}',
      '.pw-btn{height:38px;padding:0 16px;border-radius:8px;border:none;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit}',
      '.pw-btn-red{background:#E8192C;color:#fff}.pw-btn-red:hover{opacity:.88}',
      '.pw-btn-ghost{background:#fff;border:1px solid #e8e8e8;color:#555}.pw-btn-ghost:hover{background:#fafafa}',

      /* ── Input page enhancements ── */
      '@keyframes pw-spin{to{transform:rotate(360deg)}}',
      '#pw-check-section{margin-top:14px;background:#fff;border-radius:12px;border:1px solid #e8e8e8;overflow:hidden}',
      '.pw-check-hdr{padding:13px 18px;border-bottom:1px solid #f0f0f0;font-size:13px;font-weight:500;color:#1a1a1a;display:flex;align-items:center;gap:8px}',
      '.pw-check-hdr-icon{width:7px;height:7px;border-radius:50%;background:#E8192C;flex-shrink:0;animation:pw-spin 1s linear infinite}',
      '.pw-check-grid{display:grid;grid-template-columns:repeat(3,1fr)}',
      '.pw-check-block{padding:13px 15px;border-left:1px solid #f0f0f0;transition:background .4s}',
      '.pw-check-block:first-child{border-left:none}',
      '.pw-check-block.pw-cb-running{background:#fffafa}',
      '.pw-check-block.pw-cb-done-ok{background:#f9fef9}',
      '.pw-check-block.pw-cb-done-high{background:#fff9f9}',
      '.pw-check-block.pw-cb-done-med{background:#fffdf4}',
      '.pw-cb-num{font-size:10px;font-weight:600;color:#bbb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}',
      '.pw-cb-title{font-size:12px;font-weight:500;color:#1a1a1a;margin-bottom:5px}',
      '.pw-cb-desc{font-size:11px;color:#aaa;line-height:1.45}',
      '.pw-cb-spin-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}',
      '.pw-cb-spinner{width:13px;height:13px;border:2px solid #f0f0f0;border-top-color:#E8192C;border-radius:50%;animation:pw-spin .75s linear infinite;flex-shrink:0}',
      '.pw-cb-running-lbl{font-size:11px;font-weight:600;color:#E8192C}',
      '.pw-cb-done-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}',
      '.pw-cb-done-icon{width:15px;height:15px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}',
      '.pw-cb-done-icon svg{width:8px;height:8px}',
      '.pw-cb-done-lbl{font-size:11px;font-weight:600}',
      '#pw-balance-note{font-size:12px;color:#aaa;margin-top:10px;padding:0 2px;line-height:1.5}',
      '#pw-balance-note b{font-weight:500;color:#E8192C}',
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

  fNumber(ui.balance || 0);
    note.innerHTML = 'Базовая проверка — бесплатно &nbsp;·&nbsp; Полный отчёт — <b>$1.00</b> с баланса &nbsp;·&nbsp; Баланс: <b>$' + bal.toFixed(2) + '</b>';
  }

  function setBlockIdle(el, b) {
    el.className = 'pw-check-block';
    el.innerHTML =
      '<div class="pw-cb-num">Блок ' + b.num + '</div>' +
      '<div class="pw-cb-title">' + b.title + '</div>' +
      '<div class="pw-cb-desc">' + b.desc + '</div>';
  }

  function setBlockWaiting(el, b) {
    el.className = 'pw-check-block';
    el.innerHTML =
      '<div class="pw-cb-num">Блок ' + b.num + '</div>' +
      '<div class="pw-cb-title" style="color:#ccc">' + b.title + '</div>' +
      '<div class="pw-cb-desc" style="color:#e0e0e0">' + b.desc + '</div>';
  }

  function setBlockRunning(el, b) {
    el.className = 'pw-check-block pw-cb-running';
    el.innerHTML =
      '<div class="pw-cb-spin-row">' +
        '<div class="pw-cb-spinner"></div>' +
        '<div class="pw-cb-running-lbl">Блок ' + b.num + ' · Анализируем...</div>' +
      '</div>' +
      '<div class="pw-cb-desc">' + b.desc + '</div>';
  }

  function setBlockDone(el, b) {
    var cfg = {
      ok:   { bg: '#dcfce7', color: '#16a34a', blockCls: 'pw-cb-done-ok',
               icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>' },
      high: { bg: '#fee2e2', color: '#dc2626', blockCls: 'pw-cb-done-high',
               icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>' },
      med:  { bg: '#fef3c7', color: '#d97706', blockCls: 'pw-cb-done-med',
               icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' },
    };
    var c = cfg[b.doneCls] || cfg.ok;
    el.className = 'pw-check-block ' + c.blockCls;
    el.innerHTML =
      '<div class="pw-cb-done-row">' +
        '<div class="pw-cb-done-icon" style="background:' + c.bg + ';color:' + c.color + '">' + c.icon + '</div>' +
        '<div class="pw-cb-done-lbl" style="color:' + c.color + '">Блок ' + b.num + ' · ' + b.doneLabel + '</div>' +
      '</div>' +
      '<div class="pw-cb-desc" style="color:#c8c8c8">' + b.desc + '</div>';
  }

  function renderCheckSectionIdle(sec) {
    sec.innerHTML = '';
    var hdr = h('div', { class: 'pw-check-hdr' }, 'Что проверяет аудит');
    sec.appendChild(hdr);
    var grid = h('div', { class: 'pw-check-grid' });
    _CHECK_BLOCKS.forEach(function (b) {
      var block = h('div', { class: 'pw-check-block', id: 'pw-cb-' + b.num });
      setBlockIdle(block, b);
      grid.appendChild(block);
    });
    sec.appendChild(grid);
  }

  function enhanceInputPage(store) {
    var inputCard = findInputCard();
    if (!inputCard) return;

    if (!_inputEnhanced) {
      _inputEnhanced = true;

      // Обновить подзаголовок
      var texts = inputCard.querySelectorAll('p, .text-500, .text-color-secondary, div');
      for (var i = 0; i < texts.length; i++) {
        var t = texts[i];
        if (t.children.length === 0 && (t.textContent.indexOf('Введите URL') !== -1 || t.textContent.indexOf('минут') !== -1)) {
          t.textContent = 'Проверка перед подключением к AdSense';
          break;
        }
      }

      // Заменить кнопку текст "Начать аудит" → "Проверить канал"
      var btns = inputCard.querySelectorAll('button');
      for (var j = 0; j < btns.length; j++) {
        if (btns[j].textContent.indexOf('Начать') !== -1 || btns[j].textContent.indexOf('аудит') !== -1) {
          btns[j].textContent = 'Проверить канал';
          break;
        }
      }

      // Добавить balance note
      var note = h('div', { id: 'pw-balance-note' });
      inputCard.appendChild(note);
      updateBalanceNote(store);

      // Добавить секцию "Что проверяет аудит"
      var sec = h('div', { id: 'pw-check-section' });
      renderCheckSectionIdle(sec);
      var container = inputCard.parentElement;
      if (container) container.appendChild(sec);
    } else {
      updateBalanceNote(store);
    }
  }

  function showCheckSection() {
    var sec = document.getElementById('pw-check-section');
    if (sec) sec.style.display = '';
    var note = document.getElementById('pw-balance-note');
    if (note) note.style.display = '';
  }

  function hideCheckSection() {
    var sec = document.getElementById('pw-check-section');
    if (sec) sec.style.display = 'none';
    var note = document.getElementById('pw-balance-note');
    if (note) note.style.display = 'none';
  }

  function startPreloader() {
    if (_preloaderActive) return;
    _preloaderActive = true;

    var sec = document.getElementById('pw-check-section');
    if (!sec) return;
    sec.style.display = '';

    // Обновить заголовок — добавить пульсирующую точку
    var hdr = sec.querySelector('.pw-check-hdr');
    if (hdr) {
      hdr.innerHTML = '<div class="pw-check-hdr-icon"></div>Выполняется проверка...';
    }

    // Сначала все блоки в режим ожидания
    _CHECK_BLOCKS.forEach(function (b) {
      var el = document.getElementById('pw-cb-' + b.num);
      if (el) setBlockWaiting(el, b);
    });

    // Последовательная анимация: block → running (1300ms) → done
    var runDuration = 1300;
    var gaps        = [0, runDuration + 200, (runDuration + 200) * 2];

    _CHECK_BLOCKS.forEach(function (b, i) {
      setTimeout(function () {
        var el = document.getElementById('pw-cb-' + b.num);
        if (el) setBlockRunning(el, b);
        setTimeout(function () {
          var el2 = document.getElementById('pw-cb-' + b.num);
          if (el2) setBlockDone(el2, b);
          // После последнего блока — обновить заголовок
          if (i === _CHECK_BLOCKS.length - 1) {
            var hdr2 = sec.querySelector('.pw-check-hdr');
            if (hdr2) hdr2.innerHTML = 'Анализ завершён · Формируем отчёт...';
          }
        }, runDuration);
      }, gaps[i]);
    });
  }

  // ── Preview-карточка (не оплачено) ───────────────────────────────────────
  function buildPreviewCard(report, store) {
    var card = h('div', { class: 'pw-card' });

    var hdr = h('div', { class: 'pw-card-header' });
    hdr.appendChild(h('div', { class: 'pw-card-title' }, 'Полный отчёт с рекомендациями'));
    hdr.innerHTML += '<div style="font-size:12px;color:#aaa">Стоимость: <b style="color:#E8192C">$1.00</b></div>';
    card.appendChild(hdr);

    var body = h('div', { class: 'pw-card-body' });

    // Metrics grid from store.preview
    var preview = store && (store.preview || store.previewData || null);
    var grid = buildMetricsGrid(preview);
    if (grid) body.appendChild(grid);

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
    var gateText = h('div', { class: 'pw-blur-gate-text' }, 'Детальный разбор и рекомендации скрытыя');

    var unlockInfo = (report.unlock_info) || (store && store.unlockInfo) || {};
    var balance    = Number(unlockInfo.balance || 0);
    var btnText    = 'Открыть полный отчёт —  $1.00';
    if (balance > 0) {
      btnText = 'Открыть полный отчёт — $1.00 (баланс: $' + balance.toFixed(2) + ')';
    } else if (unlockInfo.credit_available) {
      btnText = 'Получить отчёт (бесплатно)';
    }

    var errMsg = h('div', { class: 'pw-unlock-error', style: 'display:none' });
    var btn = h('button', { class: 'pw-unlock-btn' }, btnText);

    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = 'Оплата...';
      errMsg.style.display = 'none';
      var st = getStore();
      if (st && typeof st.unlockReport === 'function') {
        var id = (report.id != null ? report.id : null) || (st.auditId != null ? st.auditId : null);
        st.unlockReport(id).then(function () {
          var s = getStore();
          if (s && s.report) renderReport(s);
        }).catch(function (err) {
          btn.disabled = false;
          btn.textContent = btnText;
          var msg = (err && err.message) ? err.message : 'Ошибка при оплате. Попробуйте ещё раз.';
          errMsg.textContent = msg;
          errMsg.style.display = 'block';
        });
      } else {
        btn.disabled = false;
        btn.textContent = btnText;
      }
    });

    gate.appendChild(gateText);
    gate.appendChild(btn);
    gate.appendChild(errMsg);
    wrap.appendChild(gate);
    body.appendChild(wrap);
    body.appendChild(h('div', { style: 'font-size:11px;color:#ccc;text-align:center' }, 'Детальный разбор каждого сигнала · Конкретные рекомендации автору'));
    card.appendChild(body);
    return card;
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

  // ── Блок reused content (высокий уровенэ) ────────────────────────────────
  function buildReusedBox(signals) {
    var box = h('div', { class: 'pw-reused-box' });
    var highCount = signals.filter(function (s) { return s.level === 'high'; }).length;
    var title = h('div', { class: 'pw-reused-title' });
    title.innerHTML = ICONS.warn + ' Reused / Mass-produced Контент — ' + signals.length +
      ' блок' + (signals.length === 1 ? '' : signals.length < 5 ? 'а' : 'ов') + ' уровнь ' +
      (highCount >= 2 ? 'Высокого' : 'Среднего');
    box.appendChild(title);
    signals.forEach(function (sig) {
      var row  = h('div', { class: 'pw-signal-row' });
      var lmap = { high: 'pw-sig-high', medium: 'pw-sig-med', low: 'pw-sig-low' };
      var dot  = h('div', { class: 'pw-sig-dot ' + (lmap[sig.level] || 'pw-sig-med') });
      var info = h('div');
      info.appendChild(h('div', { class: 'pw-sig-title' }, sig.title || ''));
      var descText = sig.detail || sig.description || '';
      if (descText) info.appendChild(h('div', { class: 'pw-sig-val' }, descText));
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
    var descText = sig.detail || sig.description || '';
    if (descText) info.appendChild(h('div', { class: 'pw-risk-desc' }, descText));
    if (sig.recommendation) info.appendChild(h('div', { class: 'pw-risk-rec' }, sig.recommendation));
    row.appendChild(dot);
    row.appendChild(info);
    return row;
  }

  // ── Рекомендации для автора ───────────────────────────────────────────────
  function buildRecommendations(recs) {
    if (!Array.isArray(recs) || !recs.length) return null;
    var section = h('div', { class: 'pw-recs-section' });
    section.appendChild(h('div', { class: 'pw-recs-title' }, 'Рекомендации автору канала'));
    recs.forEach(function (rec, i) {
      var item = h('div', { class: 'pw-rec-item' });
      item.appendChild(h('div', { class: 'pw-rec-num' }, String(i + 1)));
      item.appendChild(h('div', { class: 'pw-rec-text' }, rec));
      section.appendChild(item);
    });
    return section;
  }

  // ── Объединение сигналов Блока 2 ─────────────────────────────────────────
  // PHP-сигналы (type, level, title, detail) + AI-сигналы (level, title, description, recommendation)
  function mergeB2Signals(full) {
    var phpSigs = (full && Array.isArray(full.php_signals)   ? full.php_signals   : []);
    var aiSigs  = (full && Array.isArray(full.block2_signals) ? full.block2_signals : []);
    // Нормализуем php_signals: добавляем поле description (синоним detail)
    var phpNorm = phpSigs.map(function (s) {
      return { level: s.level || 'medium', title: s.title || '', description: s.detail || '', recommendation: s.recommendation || null };
    });
    return phpNorm.concat(aiSigs);
  }

  // ── Полный отчёт (оплачен) ───────────────────────────────────────────────
  function buildFullReport(report, full) {
    var wrap = h('div', { class: 'pw-card' });

    // ── Получаем данные по каждому блоку ──
    var criteria = (full && Array.isArray(full.block1_criteria) ? full.block1_criteria : null);
    var b2Sigs   = mergeB2Signals(full);
    var b3Sigs   = (full && Array.isArray(full.block3_signals) ? full.block3_signals : null);
    var recs     = (full && Array.isArray(full.recommendations_for_user) ? full.recommendations_for_user : null);
    var summaryMod = (full && full.summary_for_moderator) || report.summary || null;

    // ── Риски для заголовков вкладок ──
    var b1Risk = (report.admission      && report.admission.risk)      || 'ok';
    var b2Risk = (report.demonetization && report.demonetization.risk) || 'low';
    var b3Risk = (report.copyright      && report.copyright.risk)      || 'low';

    var tabDefs = [
      { label: 'Блок 1 · Допуск',        risk: b1Risk, panelTitle: 'Обязательные критерии',      type: 'criteria',  data: criteria },
      { label: 'Блок 2 · Демонетизация', risk: b2Risk, panelTitle: 'Риски демонетизации',        type: 'signals2',  data: b2Sigs   },
      { label: 'Блок 3 · Страйки',       risk: b3Risk, panelTitle: 'Риски авторских прав',       type: 'signals3',  data: b3Sigs   },
    ];

    // ── Tab row ──
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

    // ── Панели ──
    tabDefs.forEach(function (td, i) {
      var panel = h('div', { class: 'pw-tab-panel', style: i === 0 ? '' : 'display:none' });

      // Подзаголовок с бейджем риска
      var phdr = h('div', { style: 'display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:6px' });
      phdr.appendChild(h('div', { style: 'font-size:13px;font-weight:500;color:#1a1a1a' }, td.panelTitle));
      phdr.innerHTML += badge(td.risk);
      panel.appendChild(phdr);

      if (td.type === 'criteria') {
        // Блок 1: список критериев
        if (criteria && criteria.length) {
          var crList = h('div', { class: 'pw-cr-list' });
          criteria.forEach(function (c) { crList.appendChild(buildCriteriaRow(c)); });
          panel.appendChild(crList);
        } else if (report.admission && report.admission.details) {
          panel.appendChild(h('div', { style: 'font-size:12px;line-height:1.7;color:#555' }, report.admission.details));
        } else {
          panel.appendChild(h('p', { style: 'font-size:12px;color:#aaa' }, 'Данные блока не обнаружены'));
        }

      } else if (td.type === 'signals2') {
        // Блок 2: высокие сигналы в reused-box, остальные — отдельно
        if (b2Sigs.length) {
          var highSigs = b2Sigs.filter(function (s) { return s.level === 'high'; });
          var otherSigs = b2Sigs.filter(function (s) { return s.level !== 'high'; });

          if (highSigs.length >= 2) {
            panel.appendChild(buildReusedBox(highSigs));
          } else if (highSigs.length === 1) {
            // Один высокий — тоже показываем в reused-box
            panel.appendChild(buildReusedBox(highSigs));
          }

          if (otherSigs.length) {
            var sectTitle = h('div', { class: 'pw-risk-section-title' }, 'Дополнительные сигналы');
            panel.appendChild(sectTitle);
            otherSigs.forEach(function (sig) { panel.appendChild(buildRiskRow(sig)); });
          }

          // Если только средние сигналы (нет высоких)
          if (!highSigs.length && !otherSigs.length) {
            panel.appendChild(h('p', { style: 'font-size:12px;color:#aaa' }, 'Сигналы демонетизации не обнаружены'));
          }
        } else if (report.demonetization && report.demonetization.details) {
          panel.appendChild(h('div', { style: 'font-size:12px;line-height:1.7;color:#555' }, report.demonetization.details));
        } else {
          panel.appendChild(h('p', { style: 'font-size:12px;color:#16a34a' }, 'Значимых сигналов демонетизации не обнаружено'));
        }

      } else if (td.type === 'signals3') {
        // Блок 3: риски страйков
        if (b3Sigs && b3Sigs.length) {
          b3Sigs.forEach(function (sig) { panel.appendChild(buildRiskRow(sig)); });
        } else if (report.copyright && report.copyright.details) {
          panel.appendChild(h('div', { style: 'font-size:12px;line-height:1.7;color:#555' }, report.copyright.details));
        } else {
          panel.appendChild(h('p', { style: 'font-size:12px;color:#16a34a' }, 'Значимых рисков авторских прав не обнаружено'));
        }
      }

      panels.push(panel);
      wrap.appendChild(panel);
    });

    // ── Итог для модератора ──
    if (summaryMod) {
      var note = h('div', { class: 'pw-flag-note' });
      note.innerHTML = '<strong>Итог для модератора:</strong> ' + summaryMod;
      wrap.appendChild(note);
    }

    // ── Рекомендации для автора ──
    var recsEl = buildRecommendations(recs);
    if (recsEl) wrap.appendChild(recsEl);

    // ── Кнопка ──
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

  // ── Главная функция рендера ──────────────────────────────────────────────
  function removeInject() {
    var el = document.getElementById('pw-audit-inject');
    if (el) el.remove();
    var ar = document.querySelector('.audit-result');
    if (ar) ar.style.display = '';
    var ub = document.querySelector('.audit-unlock-button');
    if (ub) ub.style.display = '';
    // Восстановить input-page секции
    showCheckSection();
    _preloaderActive = false;
  }

  // ── Кеш и загрузка полных данных аудита из REST API ─────────────────────
  var _pwApiCache = {};

  function fetchAuditFull(auditId, cb) {
    if (_pwApiCache[auditId]) { cb(_pwApiCache[auditId]); return; }
    var nonce = (window.paywayAuditCfg && window.paywayAuditCfg.nonce) || '';
    fetch('/wp-json/payway/v1/audit/' + auditId + '/status', {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': nonce }
    })
    .then(function (r) { return r.json(); })
    .then(function (d) { _pwApiCache[auditId] = d; cb(d); })
    .catch(function () { cb({}); });
  }

  function renderReport(store, _apiData) {
    var report = store.report;
    if (!report) return;

    var auditResult = document.querySelector('.audit-result');
    if (!auditResult) return;

    var container = auditResult.parentElement;
    if (!container) return;

    var inject = document.getElementById('pw-audit-inject');
    if (!inject) {
      inject = h('div', { id: 'pw-audit-inject' });
      container.insertBefore(inject, auditResult);
    }

    inject.innerHTML = '';

    // Богатые данные: сначала из apiData (прямой fetch), потом из store
    var full    = (_apiData && _apiData.full)    || store.full    || store.reportFull || null;
    var preview = (_apiData && _apiData.preview) || store.preview || null;

    // 1. Вердикт
    inject.appendChild(buildVerdictBanner(report));

    // 2. Три блока-карточки
    inject.appendChild(buildBlocksRow(report));

    // 3. Основной контент
    var isPaid = store.isPaid || (report && report.is_paid);

    var hasApiData = _apiData && _apiData.full;
    if (isPaid && !full && !hasApiData && store.auditId) {
      // Данные ещё не загружены — fetches API и перерендерит
      inject.appendChild(buildPreviewCard(report, store));
      fetchAuditFull(store.auditId, function (apiData) {
        renderReport(store, apiData || {});
      });
    } else {
      inject.appendChild(isPaid ? buildFullReport(report, full) : buildPreviewCard(report, store));
    }

    // Скрываем оригинальные Vue-секции
    auditResult.style.display = 'none';
    var unlockDiv = document.querySelector('.audit-unlock-button');
    if (unlockDiv) unlockDiv.style.display = 'none';
    // Скрываем input-page секции (check-section, balance note)
    hideCheckSection();
  }

  // ── Цикл опроса store ────────────────────────────────────────────────────
  function tryRender(attempts) {
    if (attempts <= 0) return;
    var store = getStore();
    if (!store) {
      setTimeout(function () { tryRender(attempts - 1); }, 400);
      return;
    }

    // Улучшаем input-страницу сразу при idle
    if (!store.status || store.status === 'idle') {
      enhanceInputPage(store);
    }

    if (store.status === 'done' && store.report) {
      renderReport(store);
    }

    var lastKey = (store.auditId || '') + '/' + (store.isPaid ? '1' : '0') + '/' + (store.status || '');

    setInterval(function () {
      var s = getStore();
      if (!s) return;

      var currKey = (s.auditId || '') + '/' + (s.isPaid ? '1' : '0') + '/' + (s.status || '');

      // Обновить баланс при каждом тике (мог измениться после оплаты)
      if (s.status === 'idle' || !s.status) updateBalanceNote(s);

      if (currKey !== lastKey) {
        lastKey = currKey;
        if (s.status === 'done' && s.report) {
          renderReport(s);
        } else if (s.status && s.status !== 'idle') {
          // Аудит запущен — показать прелоадер
          startPreloader();
          removeInject();
        } else {
          // Вернулись к idle (новый аудит)
          _inputEnhanced  = false;
          _preloaderActive = false;
          removeInject();
          enhanceInputPage(s);
        }
      }

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
