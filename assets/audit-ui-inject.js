/**
 * PayWay Audit UI Injector v4.7-loader
 * Читает данные из Pinia store и переестраивает DOM под прототип v2
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
 
  // —— CSS (одноразовый инжект) —————————————————————————————————————————————————————————
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
      /* Sprint 5: recommendations redesign */
      '.pw-recs-section{padding:0 18px 16px}',
      '.pw-recs-title{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#bbb;padding:4px 0 10px}',
      '.pw-rec-list{display:flex;flex-direction:column;gap:10px}',
      '.pw-rec-item{display:flex;align-items:flex-start;gap:10px;padding:11px 13px;background:#f9fafb;border-radius:8px;border:1px solid #f0f0f0}',
      '.pw-rec-num{width:20px;height:20px;border-radius:50%;background:#E8192C;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;color:#fff;flex-shrink:0;margin-top:1px}',
      '.pw-rec-title{font-size:13px;font-weight:500;color:#1a1a1a;margin-bottom:3px}',
      '.pw-rec-text{font-size:12px;color:#555;line-height:1.5}',
      '.pw-rec-tag{font-size:10px;padding:1px 6px;border-radius:4px;background:#ffeaeb;color:#E8192C;font-weight:500;margin-top:5px;display:inline-block}',
      '.pw-rec-tag.important{background:#fffbeb;color:#d97706}',
      '.pw-rec-tag.recommended{background:#f0f0f0;color:#555}',
      /* Sprint 5: moderator checklist */
      '.pw-mod-block{margin-top:16px}',
      '.pw-mod-summary{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;font-size:12px;color:#92400e;line-height:1.6;margin-bottom:12px}',
      '.pw-mod-summary strong{color:#78350f}',
      '.pw-checklist{display:flex;flex-direction:column;gap:6px}',
      '.pw-check-item{display:flex;align-items:flex-start;gap:8px;font-size:12px;color:#555;padding:7px 10px;background:#f9fafb;border-radius:7px;line-height:1.5}',
      '.pw-check-num{width:18px;height:18px;border-radius:50%;background:#d97706;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:600;color:#fff;flex-shrink:0;margin-top:1px}',
 
      /* Action row */
      '.pw-action-row{display:flex;gap:10px;flex-wrap:wrap;padding:0 16px 16px}',
      '.pw-btn{height:38px;padding:0 16px;border-radius:8px;border:none;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit}',
      '.pw-btn-red{background:#E8192C;color:#fff}.pw-btn-red:hover{opacity:.88}',
      '.pw-btn-ghost{background:#fff;border:1px solid #e8e8e8;color:#555}.pw-btn-ghost:hover{background:#fafafa}',
 
      /* Reject banner */
      '.pw-reject-banner{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 18px;margin-bottom:16px}',
      '.pw-reject-title{font-size:14px;font-weight:600;color:#b91c1c;display:flex;align-items:center;gap:7px;margin-bottom:4px}',
      '.pw-reject-desc{font-size:13px;color:#991b1b;margin-bottom:10px;line-height:1.5}',
      '.pw-retry-pill{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #fca5a5;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:500;color:#991b1b}',
 
      /* Channel card */
      '.pw-ch-card{background:#fff;border:1px solid #e8e8e8;border-radius:10px;padding:14px 18px;margin-bottom:16px}',
      '.pw-ch-header{display:flex;align-items:center;gap:12px;margin-bottom:14px}',
      '.pw-ch-avatar{width:48px;height:48px;border-radius:50%;object-fit:cover}',
      '.pw-ch-av-ph{width:48px;height:48px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:600;color:#9d174d;flex-shrink:0}',
      '.pw-ch-name{font-size:15px;font-weight:500;color:#1a1a1a}',
      '.pw-ch-sub{font-size:12px;color:#aaa;margin-top:2px}',
      '.pw-stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}',
      '.pw-stat{background:#f9f9f9;border-radius:8px;padding:10px 12px}',
      '.pw-stat-label{font-size:10px;color:#aaa;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px}',
      '.pw-stat-val{font-size:18px;font-weight:600;letter-spacing:-.3px}',
      '.pw-stat-val.warn{color:#dc2626}',
      '.pw-stat-hint{font-size:11px;margin-top:3px;color:#aaa}',
      '.pw-stat-hint.warn{color:#dc2626}',
      '.pw-stat-val.good{color:#16a34a}',
      '.pw-stat-hint.good{color:#16a34a}',
      /* Sprint 3: criterion explanations */
      '.pw-cr-explain{font-size:11px;color:#555;margin-top:5px;padding:5px 9px;background:#f9f9f9;border-radius:5px;border-left:2px solid #e8e8e8;line-height:1.5}',
      /* Sprint 3: content rules (block 3) */
      '.pw-content-rules{margin-top:12px;padding:12px 14px;background:#f9fafb;border-radius:8px;border:1px solid #f0f0f0}',
      '.pw-rules-title{font-size:12px;font-weight:500;color:#1a1a1a;margin-bottom:8px}',
      '.pw-rule-ok,.pw-rule-no{display:flex;gap:8px;font-size:12px;padding:3px 0}',
      '.pw-rule-ok span{color:#16a34a;font-weight:600;flex-shrink:0}',
      '.pw-rule-no span{color:#dc2626;font-weight:600;flex-shrink:0}',
      /* Sprint 4: video table */
      '.pw-video-table{width:100%;border-collapse:collapse;font-size:12px;table-layout:fixed}',
      '.pw-video-table th{font-size:10px;font-weight:600;color:#bbb;text-align:left;padding:0 6px 8px;border-bottom:1px solid #f0f0f0;text-transform:uppercase;letter-spacing:.04em}',
      '.pw-video-table td{padding:8px 6px;border-bottom:1px solid #f7f7f7;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle}',
      '.pw-video-table tr:last-child td{border-bottom:none}',
      '.pw-vr-err td{background:#fef2f2}',
      '.pw-vr-warn td{background:#fffbeb}',
      '.pw-er-chip{font-size:10px;padding:2px 6px;border-radius:4px;font-weight:500}',
      '.pw-er-hi{background:#fef2f2;color:#dc2626}',
      '.pw-er-md{background:#fffbeb;color:#d97706}',
      '.pw-er-lo{background:#f0fdf4;color:#16a34a}',
      '.pw-issue-chip{font-size:10px;padding:2px 6px;border-radius:4px;background:#fef2f2;color:#dc2626;font-weight:500;margin-right:3px}',
      '.pw-table-note{font-size:11px;color:#aaa;margin-bottom:10px}',
      '.pw-table-legend{display:flex;gap:16px;margin-top:8px;font-size:11px;color:#aaa;flex-wrap:wrap}',
      '.pw-legend-sq{width:10px;height:10px;border-radius:2px;display:inline-block;margin-right:4px;vertical-align:middle}',
    ].join('');
    document.head.appendChild(style);
  }
 
  // —— SVG иконки ——————————————————————————————————————————————————————————————————————————————
  var ICONS = {
    check:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    warn:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    check_v: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  };
 
  // —— Вспомогательные функции —————————————————————————————————————————————————————————————————
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
 
  // —— Sprint 2: форматирование чисел ————————————————————————
  function formatNum(n) {
    n = Number(n) || 0;
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(0) + 'k';
    return n.toString();
  }
 
  // —— Pinia store ——————————————————————————————————————————————————————————————————————————————
  function getStore() {
    try {
      var el = document.querySelector('[data-v-app]');
      if (!el || !el.__vue_app__) return null;
      var pinia = el.__vue_app__.config.globalProperties.$pinia;
      if (!pinia || !pinia._s) return null;
      return pinia._s.get('audit');
    } catch (e) { return null; }
  }
 
  // —— Вердикт: вфвести из рисков блоков если явно не задан —————————————————————————————————
  function deriveVerdict(report) {
    if (report.verdict) return report.verdict;
    var b1 = (report.admission      && report.admission.risk)      || 'ok';
    var b2 = (report.demonetization && report.demonetization.risk) || 'low';
    var b3 = (report.copyright      && report.copyright.risk)      || 'low';
    if (b1 === 'high' || b1 === 'fail') return 'reject';
    if (b2 === 'high' || b3 === 'high' || b2 === 'medium' || b3 === 'medium') return 'manual';
    return 'accept';
  }
 
  // —— Verdict Banner ———————————————————————————————————————————————————————————————————————————
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
 
  // —— Sprint 2: Reject Banner ———————————————————————————————————
  function buildRejectBanner(full, report) {
    // Show only for reject verdict or block1 fail
    var verdict = (report && report.verdict) || (full && full.verdict) || '';
    var b1status = (full && full.block1_status) || (report && report.block1_status) || '';
    if (verdict !== 'reject' && b1status !== 'fail') return null;
 
    var criteria = (full && Array.isArray(full.block1_criteria)) ? full.block1_criteria : [];
    // Also check preview criteria
    if (!criteria.length && report && report.preview && Array.isArray(report.preview.block1_criteria)) {
      criteria = report.preview.block1_criteria;
    }
 
    // Priority order for finding primary fail reason: age > madeForKids > videoCount > others
    var priorityNames = ['Возраст', 'возраст', 'age', 'Сделано для детей', 'madeForKids', 'детский', 'Минимум', 'видео', 'video'];
    var failCriteria = criteria.filter(function(c) { return c.status === 'fail'; });
 
    var primaryFail = null;
    if (failCriteria.length) {
      // Try to find by priority
      for (var pi = 0; pi < priorityNames.length && !primaryFail; pi++) {
        for (var fi = 0; fi < failCriteria.length; fi++) {
          if ((failCriteria[fi].name || '').toLowerCase().indexOf(priorityNames[pi].toLowerCase()) !== -1) {
            primaryFail = failCriteria[fi];
            break;
          }
        }
      }
      if (!primaryFail) primaryFail = failCriteria[0];
    }
 
    var primaryReason = primaryFail ? (primaryFail.name + ': ' + (primaryFail.detail || '')) : 'Канал не прошёл проверку допуска';
 
    var cm = (full && full.channel_metrics) || {};
    var retryDate = cm.retry_date || '';
    var monthsLeft = cm.retry_months_left || '';
 
    var xIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
 
    var el = h('div', { class: 'pw-reject-banner' });
    el.appendChild(h('div', { class: 'pw-reject-title' }, xIcon + ' Причина отказа'));
 
    // Priority action from AI (Sprint 1 field)
    var priorityAction = (full && full.priority_action) || '';
    var descText = priorityAction || primaryReason;
    el.appendChild(h('div', { class: 'pw-reject-desc' }, descText));
 
    if (retryDate) {
      var pillText = '📅 Повторная подача: ' + retryDate;
      if (monthsLeft) pillText += ' (через ' + monthsLeft + ' мес.)';
      el.appendChild(h('div', { class: 'pw-retry-pill' }, pillText));
    }
 
    return el;
  }
 
  // —— Sprint 2: Channel Card ————————————————————————————————————
  function buildChannelCard(cm, full) {
    // FIX sprint 6.4: если channel_title нет — данные ещё не пришли, не рендерить карточку
    if (!cm || !cm.channel_title) return null;
 
    var el = h('div', { class: 'pw-ch-card' });
 
    // Header: avatar + name + handle + niche + created date
    var header = h('div', { class: 'pw-ch-header' });
 
    var thumbUrl = cm.channel_thumb || '';
    if (thumbUrl) {
      header.appendChild(h('img', { class: 'pw-ch-avatar', src: thumbUrl, alt: '' }));
    } else {
      var initials = (cm.channel_title || cm.title || '??').substring(0, 2).toUpperCase();
      header.appendChild(h('div', { class: 'pw-ch-av-ph' }, initials));
    }
 
    var info = h('div');
 
    // Channel name
    var nameText = cm.channel_title || cm.title || '';
    info.appendChild(h('div', { class: 'pw-ch-name' }, nameText));
 
    // Sub line: handle · niche · created date
    var subParts = [];
 
    var handle = cm.channel_handle || cm.customUrl || '';
    if (handle) {
      handle = handle.replace(/^@/, '');
      subParts.push('@' + handle);
    }
 
    // Niche from topic_categories - take last segment of last URL
    var topics = cm.topic_categories || cm.topicCategories || [];
    if (topics.length) {
      var lastTopic = topics[topics.length - 1];
      var segments = lastTopic.split('/');
      var niche = segments[segments.length - 1] || '';
      if (niche) subParts.push(niche);
    }
 
    // Created date formatted in Russian
    var createdAt = cm.channel_created_at || cm.publishedAt || '';
    if (createdAt) {
      try {
        var d = new Date(createdAt);
        var fmt = new Intl.DateTimeFormat('ru', { day: 'numeric', month: 'long', year: 'numeric' });
        subParts.push(fmt.format(d));
      } catch(e) {}
    }
 
    if (subParts.length) {
      info.appendChild(h('div', { class: 'pw-ch-sub' }, subParts.join(' · ')));
    }
 
    header.appendChild(info);
    el.appendChild(header);
 
    // Stats grid: 4 cards
    var statsGrid = h('div', { class: 'pw-stats-grid' });
 
    var subs = Number(cm.subscriber_count || 0);
    var views = Number(cm.view_count || 0);
    var er = Number(cm.avg_er || 0);
    var vpm = Number(cm.videos_per_month || 0);
    var erWarn = er < 1.5;
    var erGood = er >= 2.0;
 
    // Get metric explanations from full data
    // Sprint 6.4: доверять AI только если он указал конкретные проценты
    var metricExpl = (full && full.metric_explanations) || {};
    var aiErExpl = (metricExpl && metricExpl.er) || '';
    var aiHasNorm = /\d+[–\-]?\d*\s*%/.test(aiErExpl); // содержит «X%» или «X–Y%»
    var erHint = aiHasNorm
      ? aiErExpl
      : (erWarn ? 'Низкий. Норма для YouTube: 2–5%'
        : erGood ? 'В норме (норма YouTube: 2–5%)'
        : 'Пограничное значение (норма YouTube: 2–5%)');
 
    function statCard(label, value, hint, warn, good) {
      var card = h('div', { class: 'pw-stat' });
      card.appendChild(h('div', { class: 'pw-stat-label' }, label));
      var valCls = 'pw-stat-val' + (warn ? ' warn' : good ? ' good' : '');
      card.appendChild(h('div', { class: valCls }, value));
      if (hint) card.appendChild(h('div', { class: 'pw-stat-hint' + (warn ? ' warn' : good ? ' good' : '') }, hint));
      return card;
    }
 
    statsGrid.appendChild(statCard('Подписчики', formatNum(subs), '', false, false));
    statsGrid.appendChild(statCard('Просмотры', formatNum(views), '', false, false));
    statsGrid.appendChild(statCard('ER', er.toFixed(2) + '%', erHint, erWarn, erGood));
    statsGrid.appendChild(statCard('Видео/мес', vpm.toFixed(1), '', false, false));
 
    el.appendChild(statsGrid);
    return el;
  }
 
  // —— Sprint 6.4: placeholder карточки канала пока данные загружаются ——————————————————————————
  function buildChannelCardPlaceholder() {
    var el = h('div', { class: 'pw-ch-card', style: 'opacity:.5' });
    var header = h('div', { class: 'pw-ch-header' });
    header.appendChild(h('div', { class: 'pw-ch-av-ph' }, '...'));
    header.appendChild(h('div', { class: 'pw-ch-name', style: 'color:#aaa' }, 'Загрузка данных канала...'));
    el.appendChild(header);
    return el;
  }
 
  // —— 3 карточки блоков ———————————————————————————————————————————————————————————————————————
  function buildBlocksRow(report) {
    var row = h('div', { class: 'pw-blocks-row' });
    [
      { label: 'Блок 1', title: 'Допуск к монетизации',      risk: (report.admission      && report.admission.risk)      || 'ok'  },
      { label: 'Блок 2', title: 'Риск демонетизации',        risk: (report.demonetization && report.demonetization.risk) || 'low' },
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
 
  // —— Сетка метрик канала ——————————————————————————————————————————————————————————————————————
  function buildMetricsGrid(preview) {
    if (!preview) return null;
 
    var ageMonths = Number(preview.age_months || 0);
    var ageText   = ageMonths >= 12
      ? Math.floor(ageMonths / 12) + ' г. ' + (ageMonths % 12 ? (ageMonths % 12) + ' мес.' : '')
      : ageMonths + ' мес.';
 
    var vpm    = Number(preview.videos_per_month || 0);
    var er     = Number(preview.avg_er || 0);
    var subs   = Number(preview.subscriber_count || 0);
    var topics = (preview.topic_categories && preview.topic_categories.length)
      ? preview.topic_categories.join(', ').replace(/\/m\/\w+|\/\w+\/|_/g, ' ').trim()
      : (preview.country || '—');
 
    var erWarn  = er < 1 && subs > 10000;
    var vpmWarn = vpm > 20;
 
    var grid = h('div', { class: 'pw-metrics-grid' });
 
    function metricItem(label, value, warn) {
      var item = h('div', { class: 'pw-metric-item' });
      item.appendChild(h('div', { class: 'pw-metric-label' }, label));
      item.appendChild(h('div', { class: 'pw-metric-value' + (warn ? ' pw-mv-warn' : '') }, value));
      return item;
    }
 
    grid.appendChild(metricItem('Возраст канала',       ageText,                                   false));
    grid.appendChild(metricItem('Публикаций в месяц',   vpm.toFixed(1) + ' видео ' + (vpmWarn ? '⚠' : '✓'), vpmWarn));
    grid.appendChild(metricItem('Средний ER',           er.toFixed(2) + '% ' + (erWarn ? '⚠' : '✓'),        erWarn));
    grid.appendChild(metricItem('Подписчиков',          subs >= 1000 ? (subs / 1000).toFixed(1) + 'K' : String(subs), false));
 
    return grid;
  }
 
  // —— Preview-карточка (не оплачено) ———————————————————————————————————————————————————————————
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
    var gateText = h('div', { class: 'pw-blur-gate-text' }, 'Детальный разбор и рекомендации скрыты');
 
    var unlockInfo    = (report.unlock_info) || (store && store.unlockInfo) || {};
    var balance       = Number(unlockInfo.balance || 0);
    var creditStatus  = unlockInfo.credit_status || {};
    var freeRemaining = creditStatus.free_remaining || 0;
    var freeTotal     = creditStatus.free_total || 3;
    var btnText;
    if (balance >= 1) {
      btnText = 'Открыть полный отчёт — $1.00 (баланс: $' + balance.toFixed(2) + ')';
    } else if (unlockInfo.credit_available) {
      btnText = 'Получить бесплатный отчёт (' + freeRemaining + ' из ' + freeTotal + ' осталось)';
    } else if (creditStatus.daily_used) {
      btnText = 'Следующий бесплатный отчёт — завтра';
    } else {
      btnText = 'Бесплатные отчёты исчерпаны — пополните баланс';
    }
 
    var canUnlock = balance >= 1 || !!unlockInfo.credit_available;
    var errMsg = h('div', { class: 'pw-unlock-error', style: 'display:none' });
    var btn = h('button', { class: 'pw-unlock-btn' }, btnText);
    if (!canUnlock) {
      btn.disabled = true;
      btn.style.opacity = '0.5';
      btn.style.cursor = 'default';
    }
 
    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = 'Оплата...';
      errMsg.style.display = 'none';
      var st = getStore();
      if (st && typeof st.unlockReport === 'function') {
        var id = (report.id != null ? report.id : null) || (st.auditId != null ? st.auditId : null);
        st.unlockReport(id).then(function () {
          var s = getStore();
          var fetchId = getAuditIdFromUrl() || (s && s.auditId) || id;
          _pwApiCache = {}; // сбросить кэш чтобы загрузить свежие данные
          fetchAuditFull(fetchId, function(apiData) {
            if (apiData && !apiData._error) {
              renderReport(s || {}, apiData);
            } else {
              // Fallback: перезагрузить страницу
              window.location.reload();
            }
          });
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
 
  // —— Строка критерия (Блок 1) ——————————————————————————————————————————————————————————————
  // Sprint 3: explanations for block1 criteria
  var CRITERION_EXPLANATIONS = {
    age: {
      fail: function (cm) { return 'Главная причина отказа. Подайте заявку повторно после ' + ((cm && cm.retry_date) || 'истечения 6 месяцев') + '.'; },
      warn: function ()    { return 'Канал почти достиг требования. Продолжайте публиковать.'; }
    },
    longUploadsStatus: {
      warn: function () { return 'Верификация открывается при 1000+ подписчиков. Подтвердите аккаунт через SMS в YouTube Studio → Настройки.'; }
    },
    madeForKids: {
      fail: function () { return 'Детский контент не монетизируется через стандартный AdSense. Измените в YouTube Studio → Контент → Настройки → Аудитория.'; }
    },
    regularity: {
      fail: function () { return 'Обнаружена пауза > 60 дней. YouTube снижает охват нерегулярных каналов.'; }
    },
    videoCount: {
      fail: function (cm) { return 'Необходимо минимум 5 публичных видео. Сейчас: ' + ((cm && cm.video_count) || '?') + '.'; }
    }
  };
 
  function buildCriteriaRow(c, channelMetrics) {
    var status  = c.status || 'ok';
    var iconMap = { ok: ICONS.check, fail: ICONS.x, warn: ICONS.warn };
    var row = h('div', { class: 'pw-cr-row' });
    var dot = h('div', { class: 'pw-cr-dot pw-cr-' + status }, iconMap[status] || ICONS.check);
    var info = h('div');
    info.appendChild(h('div', { class: 'pw-cr-name' }, c.name || ''));
    if (c.detail) info.appendChild(h('div', { class: 'pw-cr-desc' }, c.detail));
    // Sprint 3: add explanation for fail/warn criteria
    if (status !== 'ok') {
      var key = c.key || c.id || c.criterion_key || '';
      // Fallback: определить ключ по name если key пустой
      if (!key) {
        var nameLower = (c.name || '').toLowerCase();
        if (nameLower.indexOf('возраст') !== -1 || nameLower.indexOf('age') !== -1) key = 'age';
        else if (nameLower.indexOf('верифик') !== -1 || nameLower.indexOf('uploads') !== -1) key = 'longUploadsStatus';
        else if (nameLower.indexOf('дет') !== -1 || nameLower.indexOf('kids') !== -1) key = 'madeForKids';
        else if (nameLower.indexOf('регуляр') !== -1 || nameLower.indexOf('публикац') !== -1) key = 'regularity';
        else if (nameLower.indexOf('видео') !== -1 || nameLower.indexOf('video') !== -1) key = 'videoCount';
        else if (nameLower.indexOf('подписчик') !== -1 || nameLower.indexOf('subscriber') !== -1) key = 'subscriberVisibility';
      }
      var explEntry = CRITERION_EXPLANATIONS[key];
      if (explEntry && typeof explEntry[status] === 'function') {
        var explText = explEntry[status](channelMetrics || null);
        if (explText) info.appendChild(h('div', { class: 'pw-cr-explain' }, explText));
      }
    }
    row.appendChild(dot);
    row.appendChild(info);
    return row;
  }
 
  // —— Блок reused content (высокий уровень) ——————————————————————————————————————————————
  function buildReusedBox(signals) {
    var box = h('div', { class: 'pw-reused-box' });
    var highCount = signals.filter(function (s) { return s.level === 'high'; }).length;
    var title = h('div', { class: 'pw-reused-title' });
    title.innerHTML = ICONS.warn + ' Reused / Mass-produced контент — ' + signals.length +
      ' сигнал' + (signals.length === 1 ? '' : signals.length < 5 ? 'а' : 'ов') + ' уровня ' +
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
 
  // —— Строка риска (Блоки 2/3) ——————————————————————————————————————————————————————————————
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
 
  // —— Sprint 5: Рекомендации для автора (redesign) ————————————————————————————————————————
  function buildRecommendations(recs, full) {
    var section = h('div', { class: 'pw-recs-section' });
    section.appendChild(h('div', { class: 'pw-recs-title' }, 'Рекомендации автору канала'));
    var list = h('div', { class: 'pw-rec-list' });
    var idx = 0;
 
    // 1. priority_action — first item with red badge + tag "Критично"
    var priorityAction = (full && full.priority_action) || '';
    if (priorityAction) {
      idx++;
      var paItem = h('div', { class: 'pw-rec-item' });
      paItem.appendChild(h('div', { class: 'pw-rec-num' }, String(idx)));
      var paBody = h('div');
      paBody.appendChild(h('div', { class: 'pw-rec-title' }, 'Первоочередное действие'));
      paBody.appendChild(h('div', { class: 'pw-rec-text' }, priorityAction));
      // retry_context as subtitle
      var retryCtx = (full && full.retry_context) || '';
      if (retryCtx) {
        paBody.appendChild(h('div', { class: 'pw-rec-text', style: 'margin-top:4px;color:#92400e' }, retryCtx));
      }
      paBody.appendChild(h('span', { class: 'pw-rec-tag' }, 'Критично'));
      paItem.appendChild(paBody);
      list.appendChild(paItem);
    }
 
    // 2. Rest of recommendations
    if (Array.isArray(recs) && recs.length) {
      recs.forEach(function (rec) {
        idx++;
        var item = h('div', { class: 'pw-rec-item' });
        item.appendChild(h('div', { class: 'pw-rec-num' }, String(idx)));
        var body = h('div');
 
        if (typeof rec === 'object' && rec !== null) {
          // Structured: {title, text, tag}
          if (rec.title) body.appendChild(h('div', { class: 'pw-rec-title' }, rec.title));
          body.appendChild(h('div', { class: 'pw-rec-text' }, rec.text || rec.description || ''));
          if (rec.tag) {
            var TAG_MAP = {
              'critical': { label: 'Критично', cls: '' },
              'критично': { label: 'Критично', cls: '' },
              'important': { label: 'Важно', cls: ' important' },
              'важно': { label: 'Важно', cls: ' important' },
              'recommended': { label: 'Рекомендуется', cls: ' recommended' },
              'рекомендуется': { label: 'Рекомендуется', cls: ' recommended' },
            };
            var tagKey = (rec.tag || '').toLowerCase().trim();
            var tagEntry = TAG_MAP[tagKey] || { label: rec.tag, cls: ' recommended' };
            body.appendChild(h('span', { class: 'pw-rec-tag' + tagEntry.cls }, tagEntry.label));
          }
        } else {
          // String (old format) — title = first 60 chars
          var recStr = String(rec);
          var title = recStr.length > 60 ? recStr.substring(0, 60) + '…' : recStr;
          body.appendChild(h('div', { class: 'pw-rec-title' }, title));
          if (recStr.length > 60) body.appendChild(h('div', { class: 'pw-rec-text' }, recStr));
        }
 
        item.appendChild(body);
        list.appendChild(item);
      });
    }
 
    if (idx === 0) return null;
    section.appendChild(list);
    return section;
  }
 
  // —— Sprint 5: Чеклист модератора (admin only) ——————————————————————————————————————————
  function buildModeratorChecklist(full) {
    var cfg = window.paywayAuditCfg || {};
    var isAdmin = cfg.is_admin === true || cfg.is_admin === 'true' || cfg.is_admin === '1' || cfg.is_admin === 1;
    if (!isAdmin) return null;
 
    var summaryMod = (full && full.summary_for_moderator) || '';
    var checklist = (full && Array.isArray(full.checklist_moderator)) ? full.checklist_moderator : [];
    if (!summaryMod && !checklist.length) return null;
 
    var block = h('div', { class: 'pw-mod-block' });
 
    if (summaryMod) {
      var summary = h('div', { class: 'pw-mod-summary' });
      summary.innerHTML = '<strong>Для ручной проверки:</strong> ' + summaryMod;
      block.appendChild(summary);
    }
 
    if (checklist.length) {
      var listEl = h('div', { class: 'pw-checklist' });
      checklist.forEach(function (item, i) {
        var row = h('div', { class: 'pw-check-item' });
        row.appendChild(h('div', { class: 'pw-check-num' }, String(i + 1)));
        row.appendChild(h('div', {}, typeof item === 'string' ? item : (item.text || item.title || '')));
        listEl.appendChild(row);
      });
      block.appendChild(listEl);
    }
 
    return block;
  }
 
  // —— Sprint 4: Таблица видео ———————————————————————————————————————————————————————————————
  function buildVideoTable(full) {
    var cm = (full && full.channel_metrics) || {};
    var videos = cm.videos_list || [];
    if (!videos.length) {
      return h('p', { style: 'font-size:13px;color:#aaa' }, 'Данные видео недоступны. Перезапустите аудит.');
    }
 
    var wrap = h('div');
 
    // Note
    wrap.appendChild(h('p', { class: 'pw-table-note' }, 'Последние ' + videos.length + ' видео · строки с проблемами подсвечены'));
 
    // Table
    var table = h('table', { class: 'pw-video-table', 'aria-label': 'Метрики видео канала' });
 
    // Thead
    var thead = h('thead');
    var headRow = h('tr');
    var cols = [
      { label: 'Название', style: 'width:38%' },
      { label: 'Просм.',   style: 'width:11%;text-align:right' },
      { label: 'Лайки',    style: 'width:9%;text-align:right' },
      { label: 'ER',        style: 'width:11%;text-align:right' },
      { label: 'Длина',     style: 'width:13%;text-align:right' },
      { label: 'Проблема',  style: 'width:18%' }
    ];
    cols.forEach(function (c) {
      headRow.appendChild(h('th', { style: c.style }, c.label));
    });
    thead.appendChild(headRow);
    table.appendChild(thead);
 
    // Tbody
    var tbody = h('tbody');
    videos.forEach(function (v) {
      var er = parseFloat(v.er || 0);
      var erClass = er < 0.5 ? 'pw-er-hi' : er < 1.5 ? 'pw-er-md' : 'pw-er-lo';
      var issues = Array.isArray(v.issues) ? v.issues : [];
      var rowClass = (er < 0.5 && issues.indexOf('reused') !== -1) ? 'pw-vr-err' : er < 1.5 ? 'pw-vr-warn' : '';
 
      var tr = h('tr', rowClass ? { class: rowClass } : {});
      // Sprint 6.2.2: обрезать хеш-теги, полное название в тултипе
      var titleDisplay = (v.title || '');
      var hashIdx = titleDisplay.indexOf(' #');
      if (hashIdx > 10) titleDisplay = titleDisplay.substring(0, hashIdx);
      tr.appendChild(h('td', { style: 'color:#1a1a1a', title: v.title || '' }, titleDisplay));
      tr.appendChild(h('td', { style: 'text-align:right;color:#555' }, v.view_count_fmt || String(v.view_count || 0)));
      tr.appendChild(h('td', { style: 'text-align:right;color:#555' }, String(v.like_count || 0)));
 
      var erTd = h('td', { style: 'text-align:right' });
      erTd.appendChild(h('span', { class: 'pw-er-chip ' + erClass }, (v.er || '0') + '%'));
      tr.appendChild(erTd);
 
      tr.appendChild(h('td', { style: 'text-align:right;color:#555' }, v.duration_fmt || ''));
 
      var issueTd = h('td');
      issues.forEach(function (iss) {
        issueTd.appendChild(h('span', { class: 'pw-issue-chip' }, iss));
      });
      tr.appendChild(issueTd);
 
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    wrap.appendChild(table);
 
    // Legend
    var legend = h('div', { class: 'pw-table-legend' });
    var leg1 = h('span');
    leg1.appendChild(h('span', { class: 'pw-legend-sq', style: 'background:#fef2f2;border:1px solid #fca5a5' }));
    leg1.appendChild(document.createTextNode('Красный — ER < 0.5% + reused'));
    legend.appendChild(leg1);
    var leg2 = h('span');
    leg2.appendChild(h('span', { class: 'pw-legend-sq', style: 'background:#fffbeb;border:1px solid #fde68a' }));
    leg2.appendChild(document.createTextNode('Жёлтый — ER ниже нормы'));
    legend.appendChild(leg2);
    wrap.appendChild(legend);
 
    return wrap;
  }
 
  // —— Объединение сигналов Блок 2 ——————————————————————————————————————————————————————————
  // PHP-сигналы (type, level, title, detail) + AI-сигналы (level, title, description, recommendation)
  function mergeB2Signals(full) {
    var phpSigs = (full && Array.isArray(full.php_signals)   ? full.php_signals   : []);
    var aiSigs  = (full && Array.isArray(full.block2_signals) ? full.block2_signals : []);
    // Нормализуем php_signals: добавляем поле description (синоним detail)
    var phpNorm = phpSigs.map(function (s) {
      return { type: s.type || '', level: s.level || 'medium', title: s.title || '', description: s.detail || s.description || '', recommendation: s.recommendation || null };
    });
    return phpNorm.concat(aiSigs);
  }
 
  // —— Полный отчёт (оплачен) ———————————————————————————————————————————————————————————————
  function buildFullReport(report, full) {
    var wrap = h('div', { class: 'pw-card' });
 
    // —— Получаем данные по каждому блоку ——
    var criteria = (full && Array.isArray(full.block1_criteria) ? full.block1_criteria : null);
    var channelMetrics = (full && full.channel_metrics) || {};
    // Sprint 3→6.1: deduplicate block2 — filter AI signals that duplicate PHP signals
    var phpSigTypes = (full && Array.isArray(full.php_signals) ? full.php_signals : []).map(function (s) { return s.type; }).filter(Boolean);
    // Нормализованные ключи PHP-сигналов для сравнения по тексту
    var phpNormKeys = (full && Array.isArray(full.php_signals) ? full.php_signals : [])
      .map(function (s) {
        return (s.title || '').toLowerCase().replace(/\s+/g, ' ').trim().substring(0, 40);
      }).filter(Boolean);
    var rawB2 = mergeB2Signals(full);
    // Первые N элементов — это PHP-сигналы, остальные — AI
    var phpCount = (full && Array.isArray(full.php_signals) ? full.php_signals : []).length;
    var phpB2 = rawB2.slice(0, phpCount);  // PHP-сигналы — показывать всегда
    var aiB2  = rawB2.slice(phpCount);     // AI-сигналы — фильтровать
    var aiB2Filtered = aiB2.filter(function (s) {
      // 1. Фильтр по issue_type
      if (s.issue_type && phpSigTypes.indexOf(s.issue_type) !== -1) return false;
      // 2. Фильтр по схожести заголовка с PHP-сигналами (защита от дублей без issue_type)
      var aiKey = (s.title || '').toLowerCase().replace(/\s+/g, ' ').trim().substring(0, 40);
      for (var ki = 0; ki < phpNormKeys.length; ki++) {
        if (phpNormKeys[ki].length > 5 && aiKey.indexOf(phpNormKeys[ki].substring(0,20)) !== -1) return false;
      }
      return true;
    });
    var b2Sigs = phpB2.concat(aiB2Filtered);
    var b3Sigs   = (full && Array.isArray(full.block3_signals) ? full.block3_signals : null);
    var recs     = (full && Array.isArray(full.recommendations_for_user) ? full.recommendations_for_user : null);
    var summaryMod = (full && full.summary_for_moderator) || report.summary || null;
 
    // —— Риски для заголовков вкладок —
    var b1Risk = (report.admission      && report.admission.risk)      || 'ok';
    var b2Risk = (report.demonetization && report.demonetization.risk) || 'low';
    var b3Risk = (report.copyright      && report.copyright.risk)      || 'low';
 
    var tabDefs = [
      { label: 'Блок 1 · Допуск',        risk: b1Risk, panelTitle: 'Обязательные критерии',      type: 'criteria',  data: criteria },
      { label: 'Блок 2 · Демонетизация', risk: b2Risk, panelTitle: 'Риски демонетизации',        type: 'signals2',  data: b2Sigs   },
      { label: 'Блок 3 · Страйки',       risk: b3Risk, panelTitle: 'Риски авторских прав',       type: 'signals3',  data: b3Sigs   },
      { label: 'Метрики видео',           risk: null,   panelTitle: 'Метрики видео канала',       type: 'videos',    data: full     },
    ];
 
    // —— Tab row ——
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
 
    // —— Панели ——
    tabDefs.forEach(function (td, i) {
      var panel = h('div', { class: 'pw-tab-panel', style: i === 0 ? '' : 'display:none' });
 
      // Подзаголовок с бейджем риска
      var phdr = h('div', { style: 'display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:6px' });
      phdr.appendChild(h('div', { style: 'font-size:13px;font-weight:500;color:#1a1a1a' }, td.panelTitle));
      if (td.risk !== null) phdr.innerHTML += badge(td.risk);
      panel.appendChild(phdr);
 
      if (td.type === 'criteria') {
        // Блок 1: список критериев
        if (criteria && criteria.length) {
          var crList = h('div', { class: 'pw-cr-list' });
          criteria.forEach(function (c) { crList.appendChild(buildCriteriaRow(c, channelMetrics)); });
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
        // Sprint 3 + 6.2.1: content allowed / forbidden rules
        var contentAllowed = (full && Array.isArray(full.content_allowed) && full.content_allowed.length)
          ? full.content_allowed
          : [];
        var contentForbidden = (full && Array.isArray(full.content_forbidden) && full.content_forbidden.length)
          ? full.content_forbidden
          : [];
 
        // Sprint 6.4: фоллбэк content_allowed по нише канала (topic_categories)
        if (!contentAllowed.length && b3Sigs && b3Sigs.length) {
          var niches = (channelMetrics.topic_categories || []).join(' ').toLowerCase();
          if (niches.indexOf('gaming') !== -1 || niches.indexOf('game') !== -1) {
            contentAllowed = [
              'Запись собственного геймплея с авторскими комментариями',
              'Туториалы, гайды, обзоры механик — без фрагментов чужих видео',
              'Короткие фрагменты трейлеров (< 15 сек) для обзора',
            ];
          } else if (niches.indexOf('film') !== -1 || niches.indexOf('entertain') !== -1) {
            contentAllowed = [
              'Собственный видеообзор или анализ без вставок оригинала',
              'Упоминание названий и фактов из публичных источников',
              'Короткие фрагменты (< 3 сек) для критики или комментирования',
            ];
          } else {
            // Универсальный фоллбэк для всех остальных ниш (ремонт, кулинария, образование и т.д.)
            contentAllowed = [
              'Собственный контент с авторским голосом или комментарием',
              'Упоминание названий брендов в контексте обзора или инструкции',
              'Визуальная демонстрация продуктов, купленных или предоставленных для обзора',
            ];
          }
        }
 
        // Убрать из contentForbidden пункты о длительности/конвейере — они про блок 2, не блок 3
        contentForbidden = contentForbidden.filter(function(item) {
          var lower = item.toLowerCase();
          return lower.indexOf('длительност') === -1 && lower.indexOf('конвейер') === -1;
        });
 
        if (contentAllowed.length || contentForbidden.length) {
          var rulesBox = h('div', { class: 'pw-content-rules' });
          rulesBox.appendChild(h('div', { class: 'pw-rules-title' }, 'Для данного типа контента:'));
          contentAllowed.forEach(function (item) {
            var ruleEl = h('div', { class: 'pw-rule-ok' });
            ruleEl.appendChild(h('span', {}, '✓'));
            ruleEl.appendChild(h('span', {}, item));
            rulesBox.appendChild(ruleEl);
          });
          contentForbidden.forEach(function (item) {
            var ruleEl = h('div', { class: 'pw-rule-no' });
            ruleEl.appendChild(h('span', {}, '✗'));
            ruleEl.appendChild(h('span', {}, item));
            rulesBox.appendChild(ruleEl);
          });
          panel.appendChild(rulesBox);
        }
 
      } else if (td.type === 'videos') {
        // Sprint 4: таблица видео
        var videoContent = buildVideoTable(full);
        if (videoContent) panel.appendChild(videoContent);
      }
 
      panels.push(panel);
      wrap.appendChild(panel);
    });
 
    // —— Sprint 5: Рекомендации для автора (redesign) ——
    var recsEl = buildRecommendations(recs, full);
    if (recsEl) wrap.appendChild(recsEl);
 
    // —— Sprint 5: Чеклист модератора (admin only) ——
    var modBlock = buildModeratorChecklist(full);
    if (modBlock) wrap.appendChild(modBlock);
 
    // —— Кнопка ——
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
 
  // Проверка: мы на странице /audit/ (НЕ /audit-history и НЕ /audit/?id=X)
  function isAuditFormPage() {
    var p = location.pathname.replace(/\/+$/, ''); // убрать trailing slash
    return (p === '/audit' || p.endsWith('/audit')) && !location.search;
  }
 
  // —— Sprint v4.8: Лендинговый блок для /audit/ ————————————————————————————————————————————
  function buildLandingBlock() {
    var el = h('div', { id: 'pw-audit-landing', class: 'pw-landing', style: 'padding-top:24px' });
 
    // Hero
    var hero = h('div', { class: 'pw-landing-hero', style: 'background:linear-gradient(135deg,#1a1a1a 0%,#2d1a1a 100%);border-radius:12px;padding:32px 36px;margin-bottom:20px;color:#fff' });
    hero.appendChild(h('div', { style: 'font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#E8192C;margin-bottom:10px' }, 'Инструмент для контентмейкеров'));
    hero.appendChild(h('div', { style: 'font-size:26px;font-weight:700;line-height:1.3;margin-bottom:10px' }, 'Узнайте, готов ли ваш канал к монетизации через AdSense'));
    hero.appendChild(h('div', { style: 'font-size:14px;color:#aaa;line-height:1.6;max-width:520px' }, 'Полный аудит за 1\u20132 минуты. Анализируем 20+ параметров канала через YouTube API и GPT-4o. Получите конкретные рекомендации \u2014 не общие советы, а точные числа.'));
    var badge = h('div', { style: 'display:inline-flex;align-items:center;gap:8px;background:rgba(232,25,44,.15);border:1px solid rgba(232,25,44,.3);border-radius:20px;padding:6px 14px;font-size:12px;font-weight:600;color:#ff6b7a;margin-top:16px' }, '\u2605 3 полных отчёта бесплатно \u00b7 1 в день');
    hero.appendChild(badge);
    el.appendChild(hero);
 
    // Grid карточек
    var grid = h('div', { style: 'display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px' });
 
    var cards = [
      { icon: '\u2705', title: 'Допуск к монетизации', text: 'Проверяем 6 обязательных критериев: возраст канала, регулярность публикаций, верификацию, статус madeForKids и другие.' },
      { icon: '\u26A0\uFE0F', title: 'Риски демонетизации', text: 'Выявляем признаки reused-контента, одинаковую длину видео, аномальный ER и другие сигналы для отключения монетизации.' },
      { icon: '\u00A9\uFE0F', title: 'Авторские права', text: 'Анализируем теги, названия и тематику на упоминание защищённых брендов, фильмов и франшиз. Предупреждаем о рисках Content ID страйков.' }
    ];
 
    cards.forEach(function(c) {
      var card = h('div', { style: 'background:#fff;border:1px solid #e8e8e8;border-radius:10px;padding:16px 18px' });
      card.appendChild(h('div', { style: 'font-size:22px;margin-bottom:8px' }, c.icon));
      card.appendChild(h('div', { style: 'font-size:13px;font-weight:600;color:#1a1a1a;margin-bottom:4px' }, c.title));
      card.appendChild(h('div', { style: 'font-size:12px;color:#888;line-height:1.5' }, c.text));
      grid.appendChild(card);
    });
    el.appendChild(grid);
 
    // Tech блок
    var tech = h('div', { style: 'background:#f9fafb;border:1px solid #f0f0f0;border-radius:10px;padding:16px 18px;display:flex;align-items:center;gap:24px;flex-wrap:wrap' });
    tech.appendChild(h('div', { style: 'font-size:11px;font-weight:600;color:#bbb;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap' }, 'Используем'));
    var chips = h('div', { style: 'display:flex;gap:10px;flex-wrap:wrap' });
    ['YouTube Data API v3', 'GPT-4o', '20 видео с метриками', 'PHP анализ reused-сигналов', 'AdSense критерии'].forEach(function(t) {
      chips.appendChild(h('span', { style: 'background:#fff;border:1px solid #e8e8e8;border-radius:6px;padding:4px 10px;font-size:12px;color:#555;font-weight:500' }, t));
    });
    tech.appendChild(chips);
    el.appendChild(tech);
 
    // Адаптив: mobile 1 колонка
    var style = document.createElement('style');
    style.textContent = '@media(max-width:768px){#pw-audit-landing .pw-landing-grid,#pw-audit-landing [style*="grid-template-columns"]{grid-template-columns:1fr!important}}';
    el.appendChild(style);
 
    return el;
  }
 
  // —— Sprint v4.7: Информативный прелоадер ——————————————————————————————————————————————————
  function buildLoadingScreen() {
    var el = h('div', { id: 'pw-audit-loader', style: 'padding:24px' });
 
    // Заголовок
    el.appendChild(h('div', { style: 'font-size:16px;font-weight:600;color:#1a1a1a;margin-bottom:4px' },
      'Анализируем ваш канал...'));
    el.appendChild(h('div', { style: 'font-size:13px;color:#aaa;margin-bottom:20px' },
      'Это займёт 1–2 минуты. Не закрывайте страницу.'));
 
    // Прогресс-бар
    var progressWrap = h('div', { style: 'background:#f0f0f0;border-radius:4px;height:4px;margin-bottom:24px;overflow:hidden' });
    var progressBar = h('div', { id: 'pw-progress-bar', style: 'height:4px;background:#E8192C;border-radius:4px;width:5%;transition:width 0.8s ease' });
    progressWrap.appendChild(progressBar);
    el.appendChild(progressWrap);
 
    // Чек-лист шагов
    var STEPS = [
      { id: 'step1', label: 'Получаем данные канала из YouTube API', time: 3 },
      { id: 'step2', label: 'Загружаем последние 20 видео с метриками', time: 6 },
      { id: 'step3', label: 'Вычисляем ER, частоту публикаций, длительность', time: 10 },
      { id: 'step4', label: 'Анализируем сигналы reused-контента', time: 15 },
      { id: 'step5', label: 'Проверяем критерии допуска к монетизации AdSense', time: 20 },
      { id: 'step6', label: 'Отправляем данные на анализ AI (GPT-4o)', time: 25 },
      { id: 'step7', label: 'AI оценивает риски демонетизации', time: 45 },
      { id: 'step8', label: 'AI проверяет риски авторских прав', time: 60 },
      { id: 'step9', label: 'Формируем рекомендации для вашего канала', time: 75 },
      { id: 'step10', label: 'Генерируем чеклист для модератора', time: 85 },
      { id: 'step11', label: 'Сохраняем отчёт', time: 90 },
    ];
 
    // Описание что пользователь получит
    var infoBox = h('div', { style: 'background:#f9fafb;border:1px solid #f0f0f0;border-radius:8px;padding:12px 14px;margin-bottom:16px' });
    infoBox.appendChild(h('div', { style: 'font-size:11px;font-weight:600;color:#bbb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px' }, 'Что войдёт в отчёт'));
    var features = [
      '✓ Проверка 6 критериев допуска к монетизации',
      '✓ Анализ рисков демонетизации (reused-контент, ER, частота)',
      '✓ Проверка рисков авторских прав и страйков',
      '✓ Таблица метрик по каждому из 20 видео',
      '✓ Персональные рекомендации с конкретными числами',
      '✓ Чеклист для ручной проверки модератором',
    ];
    features.forEach(function(f) {
      infoBox.appendChild(h('div', { style: 'font-size:12px;color:#555;padding:2px 0' }, f));
    });
    el.appendChild(infoBox);
 
    var stepsList = h('div', { style: 'display:flex;flex-direction:column;gap:6px' });
    STEPS.forEach(function(step) {
      var row = h('div', { id: step.id, style: 'display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:7px;background:#f9f9f9;opacity:.4;transition:opacity .4s' });
      var icon = h('div', { class: 'pw-step-icon', style: 'width:18px;height:18px;border-radius:50%;border:2px solid #e8e8e8;flex-shrink:0;display:flex;align-items:center;justify-content:center' });
      row.appendChild(icon);
      row.appendChild(h('div', { style: 'font-size:12px;color:#555' }, step.label));
      stepsList.appendChild(row);
    });
    el.appendChild(stepsList);
 
    // Анимация шагов по таймеру
    var startTime = Date.now();
    var totalDuration = 95;
    var stepTimer = setInterval(function() {
      var elapsed = (Date.now() - startTime) / 1000;
      var progress = Math.min(95, Math.round((elapsed / totalDuration) * 95));
      var bar = document.getElementById('pw-progress-bar');
      if (bar) bar.style.width = progress + '%';
 
      STEPS.forEach(function(step) {
        var row = document.getElementById(step.id);
        if (!row) return;
        if (elapsed >= step.time) {
          row.style.opacity = '1';
          var icon = row.querySelector('.pw-step-icon');
          if (icon && icon.innerHTML === '') {
            icon.style.background = '#E8192C';
            icon.style.borderColor = '#E8192C';
            icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" width="10" height="10"><path d="M20 6L9 17l-5-5"/></svg>';
          }
        }
      });
 
      // Если store уже done — убираем таймер
      var s = getStore();
      if (s && s.status === 'done') {
        clearInterval(stepTimer);
        var bar2 = document.getElementById('pw-progress-bar');
        if (bar2) bar2.style.width = '100%';
      }
    }, 500);
 
    el._stepTimer = stepTimer;
    return el;
  }
 
  // —— Главная функция рендера ——————————————————————————————————————————————————————————————
  function removeInject() {
    var el = document.getElementById('pw-audit-inject');
    // Восстановить скрытые Vue-siblings перед удалением
    if (el && el.parentElement) {
      var siblings = el.parentElement.children;
      for (var i = 0; i < siblings.length; i++) {
        if (siblings[i] !== el) {
          siblings[i].style.display = '';
        }
      }
    }
    if (el) el.remove();
    var landing = document.getElementById('pw-audit-landing');
    if (landing) landing.remove();
    var loader = document.getElementById('pw-audit-loader');
    if (loader) loader.remove();
  }
 
  // Получить ID аудита из URL (fallback если store.auditId неверный)
  function getAuditIdFromUrl() {
    try { return parseInt(new URLSearchParams(location.search).get('id')) || 0; } catch(e) { return 0; }
  }
 
  // —— Кеш и загрузка полных данных аудита из REST API ———————————————————————————————————————
  var _pwApiCache = {};
  var _pwApiFailed = {}; // ID аудитов, для которых fetch завершился ошибкой — не повторяем
  var _pwNonceRefreshed = false;
 
  // Получить свежий nonce + is_admin через admin-ajax (cookie-auth, не зависит от кеша страницы)
  function refreshNonce(cb) {
    if (_pwNonceRefreshed) { cb(); return; }
    fetch('/wp-admin/admin-ajax.php?action=payway_fresh_nonce', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.success && d.data && d.data.nonce) {
          window.paywayAuditCfg = window.paywayAuditCfg || {};
          window.paywayAuditCfg.nonce = d.data.nonce;
          if (typeof d.data.is_admin !== 'undefined') {
            window.paywayAuditCfg.is_admin = !!d.data.is_admin;
          }
          _pwNonceRefreshed = true;
        }
        cb();
      })
      .catch(function () { cb(); });
  }
 
  function fetchAuditFull(auditId, cb) {
    if (_pwApiFailed[auditId]) { cb({ _error: true }); return; }
    if (_pwApiCache[auditId]) { cb(_pwApiCache[auditId]); return; }
 
    // Сначала обновляем nonce, потом делаем запрос
    refreshNonce(function () {
      var nonce = (window.paywayAuditCfg && window.paywayAuditCfg.nonce) || '';
      fetch('/wp-json/payway/v1/audit/' + auditId + '/status', {
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': nonce }
      })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        // Если REST вернул ошибку (401, 403 и т.д.) — не кешируем как валидные данные
        if (d && (d.code || d.data && d.data.status >= 400)) {
          _pwApiFailed[auditId] = true;
          cb({ _error: true, code: d.code || 'unknown' });
          return;
        }
        _pwApiCache[auditId] = d;
        cb(d);
      })
      .catch(function () { _pwApiFailed[auditId] = true; cb({ _error: true }); });
    });
  }
 
  function renderReport(store, _apiData) {
    var report = store.report || (_apiData && _apiData.report) || _apiData || null;
    if (!report) return;
 
    var auditResult = document.querySelector('.audit-result');
    var container = auditResult
      ? auditResult.parentElement
      : document.querySelector('[data-v-app] .col:not(.col-fixed) > div');
    if (!container) return;
 
    var inject = document.getElementById('pw-audit-inject');
    if (!inject) {
      inject = h('div', { id: 'pw-audit-inject', style: 'padding-top:24px' });
      if (auditResult) {
        container.insertBefore(inject, auditResult);
      } else {
        container.appendChild(inject);
      }
    }
 
    inject.innerHTML = '';
 
    // Богатые данные: сначала из apiData (прямой fetch), потом из store
    var full    = (_apiData && _apiData.full)    || store.full    || store.reportFull || null;
    var preview = (_apiData && _apiData.preview) || store.preview || null;
 
    // Sprint 6.4: isPaid определяем раньше для placeholder логики
    var isPaid = store.isPaid || (report && report.is_paid);
 
    // Sprint 2: Reject banner (before verdict)
    var channelMetrics = (full && full.channel_metrics) || (_apiData && _apiData.full && _apiData.full.channel_metrics) || {};
    var rejectBanner = buildRejectBanner(full, report);
    if (rejectBanner) inject.appendChild(rejectBanner);
 
    // Sprint 2 + 6.4: Channel card with placeholder fallback
    var chCard = buildChannelCard(channelMetrics, full);
    if (chCard) {
      inject.appendChild(chCard);
    } else if (isPaid) {
      // Показать placeholder пока fetchAuditFull ещё не вернул данные
      inject.appendChild(buildChannelCardPlaceholder());
    }
 
    // 1. Вердикт
    inject.appendChild(buildVerdictBanner(report));
 
    // 2. Три блока-карточки
    inject.appendChild(buildBlocksRow(report));
 
    // 3. Основной контент
 
    var hasApiData = _apiData && _apiData.full;
    var apiFailed  = _apiData && _apiData._error;
    // Используем URL ID как fallback (store.auditId может быть от предыдущего аудита)
    var fetchId = getAuditIdFromUrl() || store.auditId || 0;
    if (isPaid && !full && !hasApiData && !apiFailed && fetchId) {
      // Данные ещё не загружены — fetches API и перерендерит
      inject.appendChild(buildPreviewCard(report, store));
      fetchAuditFull(fetchId, function (apiData) {
        renderReport(store, apiData || {});
      });
    } else {
      // Если оплачен — показываем полный отчёт (даже если full=null, buildFullReport использует report)
      inject.appendChild(isPaid ? buildFullReport(report, full) : buildPreviewCard(report, store));
    }
 
    // Скрываем оригинальные Vue-секции
    if (auditResult) auditResult.style.display = 'none';
    var fullReportDiv = document.querySelector('.audit-full-report');
    if (fullReportDiv) fullReportDiv.style.display = 'none';
    var unlockDiv = document.querySelector('.audit-unlock-button');
    if (unlockDiv) unlockDiv.style.display = 'none';
    // Скрыть Vue-блок "Полный отчёт заблокирован" и другие Vue-элементы в контейнере
    var siblings = container.children;
    for (var i = 0; i < siblings.length; i++) {
      if (siblings[i] !== inject && siblings[i].id !== 'pw-audit-inject') {
        siblings[i].style.display = 'none';
      }
    }
  }
 
  // —— Цикл опроса store ——————————————————————————————————————————————————————————————————————
  function tryRender(attempts) {
    if (attempts <= 0) return;
    var store = getStore();
    if (!store) {
      setTimeout(function () { tryRender(attempts - 1); }, 400);
      return;
    }
 
    if (store.status === 'done') {
      if (store.report) {
        renderReport(store);
      } else if (store.auditId) {
        // store.report пуст — грузим из API
        fetchAuditFull(store.auditId, function(apiData) {
          if (apiData && !apiData._error) renderReport(store, apiData);
        });
      }
    }
 
    // Sprint v4.8: показать лендинг если форма ещё не отправлена (только на /audit/, не на /audit-history)
    if ((!store.status || store.status === 'idle') && isAuditFormPage()) {
      var contentArea = document.querySelector('[data-v-app] .col:not(.col-fixed) > div');
      if (contentArea && !document.getElementById('pw-audit-landing')) {
        var landing = buildLandingBlock();
        if (contentArea.firstChild) {
          contentArea.insertBefore(landing, contentArea.firstChild);
        } else {
          contentArea.appendChild(landing);
        }
      }
    }
 
    var lastKey = (store.auditId || '') + '/' + (store.isPaid ? '1' : '0') + '/' + (store.status || '');
    var lastUrl = location.href;
 
    setInterval(function () {
      var s = getStore();
      if (!s) return;
 
      // Detect SPA route change — Vue Router меняет URL без перезагрузки
      var currentUrl = location.href;
      if (currentUrl !== lastUrl) {
        lastUrl = currentUrl;
        lastKey = ''; // сбросить ключ чтобы пересчитать состояние
        _pwApiCache = {};
        _pwApiFailed = {};
        removeInject();
      }
 
      // Sprint v4.8: убрать лендинг когда пользователь отправил форму
      if (s.status && s.status !== 'idle') {
        var landing = document.getElementById('pw-audit-landing');
        if (landing) landing.remove();
      }
 
      // Sprint v4.8: показать лендинг при возврате на /audit/ (SPA навигация, не на /audit-history)
      if ((!s.status || s.status === 'idle') && isAuditFormPage()) {
        var contentArea0 = document.querySelector('[data-v-app] .col:not(.col-fixed) > div');
        if (contentArea0 && !document.getElementById('pw-audit-landing')) {
          var landing2 = buildLandingBlock();
          if (contentArea0.firstChild) {
            contentArea0.insertBefore(landing2, contentArea0.firstChild);
          } else {
            contentArea0.appendChild(landing2);
          }
        }
      }
 
      // Sprint v4.7: показываем информативный прелоадер при processing/pending
      if (s.status === 'processing' || s.status === 'pending') {
        if (!document.getElementById('pw-audit-loader')) {
          // Ищем контейнер: .audit-result или основной контент-блок Vue
          var auditResult = document.querySelector('.audit-result');
          var contentArea = auditResult
            ? auditResult.parentElement
            : document.querySelector('[data-v-app] .col:not(.col-fixed) > div');
          if (contentArea) {
            var inject = document.getElementById('pw-audit-inject') || h('div', { id: 'pw-audit-inject' });
            if (!document.getElementById('pw-audit-inject')) {
              if (auditResult) {
                contentArea.insertBefore(inject, auditResult);
              } else {
                contentArea.appendChild(inject);
              }
            }
            inject.innerHTML = '';
            inject.appendChild(buildLoadingScreen());
            // Скрыть ВСЕ Vue-элементы (старый прелоадер, спиннеры и пр.)
            var siblings = contentArea.children;
            for (var si = 0; si < siblings.length; si++) {
              if (siblings[si] !== inject && siblings[si].id !== 'pw-audit-inject') {
                siblings[si].style.display = 'none';
              }
            }
          }
        }
      }
 
      var currKey = (s.auditId || '') + '/' + (s.isPaid ? '1' : '0') + '/' + (s.status || '');
 
      if (currKey !== lastKey) {
        if (s.status === 'done' && s.report) {
          lastKey = currKey;
          renderReport(s);
        } else if (s.status === 'done' && !s.report && s.auditId) {
          // Store не содержит report — грузим из API
          lastKey = currKey;
          _pwApiCache = {};
          fetchAuditFull(s.auditId, function(apiData) {
            if (apiData && apiData.report) {
              renderReport(s, apiData);
            } else if (apiData && apiData.id) {
              // API вернул данные в корне (не вложенные в report)
              renderReport(s, { report: apiData, preview: apiData.preview || {} });
            }
          });
        } else if (s.status !== 'processing' && s.status !== 'pending') {
          lastKey = currKey;
          removeInject();
        }
      }
 
      if (!document.getElementById('pw-audit-inject') && s.status === 'done' && (s.report || s.auditId)) {
        if (s.report) {
          renderReport(s);
        } else if (s.auditId) {
          fetchAuditFull(s.auditId, function(apiData) {
            if (apiData) renderReport(s, apiData);
          });
        }
      }
    }, 800);
  }
 
  // —— Старф —————————————————————————————————————————————————————————————————————————————————
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(function () { tryRender(30); }, 600);
    });
  } else {
    setTimeout(function () { tryRender(30); }, 600);
  }
 
})();
