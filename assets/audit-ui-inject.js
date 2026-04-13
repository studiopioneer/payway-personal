/**
 * PayWay Audit UI Injector v4
 * \u0427\u0438\u0442\u0430\u0435\u0442 \u0434\u0430\u043d\u043d\u044b\u0435 \u0438\u0437 Pinia store \u0438 \u043f\u0435\u0440\u0435\u0441\u0442\u0440\u0430\u0438\u0432\u0430\u0435\u0442 DOM \u043f\u043e\u0434 \u043f\u0440\u043e\u0442\u043e\u0442\u0438\u043f v2
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

  // \u2500\u2500 CSS (\u043e\u0434\u043d\u043e\u0440\u0430\u0437\u043e\u0432\u044b\u0439 \u0438\u043d\u0436\u0435\u043a\u0442) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
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
    ].join('');
    document.head.appendChild(style);
  }

  // \u2500\u2500 SVG \u0438\u043a\u043e\u043d\u043a\u0438 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  var ICONS = {
    check:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    warn:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    check_v: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  };

  // \u2500\u2500 \u0412\u0441\u043f\u043e\u043c\u043e\u0433\u0430\u0442\u0435\u043b\u044c\u043d\u044b\u0435 \u0444\u0443\u043d\u043a\u0446\u0438\u0438 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function h(tag, attrs, inner) {
    var el = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) { el.setAttribute(k, attrs[k]); });
    if (inner !== undefined) el.innerHTML = inner;
    return el;
  }

  function riskLabel(risk) {
    return ({ low: '\u041d\u0438\u0437\u043a\u0438\u0439', medium: '\u0421\u0440\u0435\u0434\u043d\u0438\u0439', high: '\u0412\u044b\u0441\u043e\u043a\u0438\u0439', ok: '\u041f\u0440\u043e\u0439\u0434\u0435\u043d', warn: '\u0412\u043d\u0438\u043c\u0430\u043d\u0438\u0435', fail: '\u041f\u0440\u043e\u0432\u0430\u043b' })[risk] || (risk || '\u041d\u0435\u0442 \u0434\u0430\u043d\u043d\u044b\u0445');
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

  // \u2500\u2500 Pinia store \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function getStore() {
    try {
      var el = document.querySelector('[data-v-app]');
      if (!el || !el.__vue_app__) return null;
      var pinia = el.__vue_app__.config.globalProperties.$pinia;
      if (!pinia || !pinia._s) return null;
      return pinia._s.get('audit');
    } catch (e) { return null; }
  }

  // \u2500\u2500 \u0412\u0435\u0440\u0434\u0438\u043a\u0442: \u0432\u0444\u0432\u0435\u0441\u0442\u0438 \u0438\u0437 \u0440\u0438\u0441\u043a\u043e\u0432 \u0431\u043b\u043e\u043a\u043e\u0432 \u0435\u0441\u043b\u0438 \u044f\u0432\u043d\u043e \u043d\u0435 \u0437\u0430\u0434\u0430\u043d \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function deriveVerdict(report) {
    if (report.verdict) return report.verdict;
    var b1 = (report.admission      && report.admission.risk)      || 'ok';
    var b2 = (report.demonetization && report.demonetization.risk) || 'low';
    var b3 = (report.copyright      && report.copyright.risk)      || 'low';
    if (b1 === 'high' || b1 === 'fail') return 'reject';
    if (b2 === 'high' || b3 === 'high' || b2 === 'medium' || b3 === 'medium') return 'manual';
    return 'accept';
  }

  // \u2500\u2500 Verdict Banner \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function buildVerdictBanner(report) {
    var v = deriveVerdict(report);
    var reason = report.verdict_reason || report.summary || '';
    var cfg = {
      accept: { cls: 'pw-verdict-accept', icon: ICONS.check_v, title: '\u041a\u0430\u043d\u0430\u043b \u0441\u043e\u043e\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u0435\u0442 \u0442\u0440\u0435\u0431\u043e\u0432\u0430\u043d\u0438\u044f\u043c \u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438' },
      reject: { cls: 'pw-verdict-reject', icon: ICONS.x,       title: '\u041a\u0430\u043d\u0430\u043b \u043d\u0435 \u0441\u043e\u043e\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u0435\u0442 \u0442\u0440\u0435\u0431\u043e\u0432\u0430\u043d\u0438\u044f\u043c' },
      manual: { cls: 'pw-verdict-manual', icon: ICONS.warn,    title: '\u0422\u0440\u0435\u0431\u0443\u0435\u0442 \u0440\u0443\u0447\u043d\u043e\u0439 \u043f\u0440\u043e\u0432\u0435\u0440\u043a\u0438' },
    }[v] || { cls: 'pw-verdict-manual', icon: ICONS.warn, title: '\u0422\u0440\u0435\u0431\u0443\u0435\u0442 \u0440\u0443\u0447\u043d\u043e\u0439 \u043f\u0440\u043e\u0432\u0435\u0440\u043a\u0438' };

    var el   = h('div', { class: 'pw-verdict ' + cfg.cls });
    var icon = h('div', { class: 'pw-v-icon' }, cfg.icon);
    var body = h('div');
    body.appendChild(h('div', { class: 'pw-v-title' }, cfg.title));
    if (reason) body.appendChild(h('div', { class: 'pw-v-sub' }, reason));
    el.appendChild(icon);
    el.appendChild(body);
    return el;
  }

  // \u2500\u2500 3 \u043a\u0430\u0440\u0442\u043e\u0447\u043a\u0438 \u0431\u043b\u043e\u043a\u043e\u0432 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function buildBlocksRow(report) {
    var row = h('div', { class: 'pw-blocks-row' });
    [
      { label: '\u0411\u043b\u043e\u043a 1', title: '\u0414\u043e\u043f\u0443\u0441\u043a \u043a \u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438',      risk: (report.admission      && report.admission.risk)      || 'ok'  },
      { label: '\u0411\u043b\u043e\u043a 2', title: '\u0420\u0438\u0441\u043a \u0434\u0435\u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438',        risk: (report.demonetization && report.demonetization.risk) || 'low' },
      { label: '\u0411\u043b\u043e\u043a 3', title: '\u0410\u0432\u0442\u043e\u0440\u0441\u043a\u0438\u0435 \u043f\u0440\u0430\u0432\u0430 / \u0441\u0442\u0440\u0430\u0439\u043a\u0438', risk: (report.copyright      && report.copyright.risk)      || 'low' },
    ].forEach(function (b) {
      var card = h('div', { class: 'pw-bcard' });
      card.appendChild(h('div', { class: 'pw-bcard-label' }, b.label));
      card.appendChild(h('div', { class: 'pw-bcard-title' }, b.title));
      card.innerHTML += badge(b.risk);
      row.appendChild(card);
    });
    return row;
  }

  // \u2500\u2500 \u0421\u0435\u0442\u043a\u0430 \u043c\u0435\u0442\u0440\u0438\u043a \u043a\u0430\u043d\u0430\u043b\u0430 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function buildMetricsGrid(preview) {
    if (!preview) return null;

    var ageMonths = Number(preview.age_months || 0);
    var ageText   = ageMonths >= 12
      ? Math.floor(ageMonths / 12) + ' \u0433. ' + (ageMonths % 12 ? (ageMonths % 12) + ' \u043c\u0435\u0441.' : '')
      : ageMonths + ' \u043c\u0435\u0441.';

    var vpm    = Number(preview.videos_per_month || 0);
    var er     = Number(preview.avg_er || 0);
    var subs   = Number(preview.subscriber_count || 0);
    var topics = (preview.topic_categories && preview.topic_categories.length)
      ? preview.topic_categories.join(', ').replace(/\/m\/\w+|\/\w+\/|_/g, ' ').trim()
      : (preview.country || '\u2014');

    var erWarn  = er < 1 && subs > 10000;
    var vpmWarn = vpm > 20;

    var grid = h('div', { class: 'pw-metrics-grid' });

    function metricItem(label, value, warn) {
      var item = h('div', { class: 'pw-metric-item' });
      item.appendChild(h('div', { class: 'pw-metric-label' }, label));
      item.appendChild(h('div', { class: 'pw-metric-value' + (warn ? ' pw-mv-warn' : '') }, value));
      return item;
    }

    grid.appendChild(metricItem('\u0412\u043e\u0437\u0440\u0430\u0441\u0442 \u043a\u0430\u043d\u0430\u043b\u0430',       ageText,                                   false));
    grid.appendChild(metricItem('\u041f\u0443\u0431\u043b\u0438\u043a\u0430\u0446\u0438\u0439 \u0432 \u043c\u0435\u0441\u044f\u0446',   vpm.toFixed(1) + ' \u0432\u0438\u0434\u0435\u043e ' + (vpmWarn ? '\u26a0' : '\u2713'), vpmWarn));
    grid.appendChild(metricItem('\u0421\u0440\u0435\u0434\u043d\u0438\u0439 ER',           er.toFixed(2) + '% ' + (erWarn ? '\u26a0' : '\u2713'),        erWarn));
    grid.appendChild(metricItem('\u041f\u043e\u0434\u043f\u0438\u0441\u0447\u0438\u043a\u043e\u0432',          subs >= 1000 ? (subs / 1000).toFixed(1) + 'K' : String(subs), false));

    return grid;
  }

  // \u2500\u2500 Preview-\u043a\u0430\u0440\u0442\u043e\u0447\u043a\u0430 (\u043d\u0435 \u043e\u043f\u043b\u0430\u0447\u0435\u043d\u043e) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function buildPreviewCard(report, store) {
    var card = h('div', { class: 'pw-card' });

    var hdr = h('div', { class: 'pw-card-header' });
    hdr.appendChild(h('div', { class: 'pw-card-title' }, '\u041f\u043e\u043b\u043d\u044b\u0439 \u043e\u0442\u0447\u0451\u0442 \u0441 \u0440\u0435\u043a\u043e\u043c\u0435\u043d\u0434\u0430\u0446\u0438\u044f\u043c\u0438'));
    hdr.innerHTML += '<div style="font-size:12px;color:#aaa">\u0421\u0442\u043e\u0438\u043c\u043e\u0441\u0442\u044c: <b style="color:#E8192C">$2.00</b></div>';
    card.appendChild(hdr);

    var body = h('div', { class: 'pw-card-body' });

    // Metrics grid from store.preview
    var preview = store && (store.preview || store.previewData || null);
    var grid = buildMetricsGrid(preview);
    if (grid) body.appendChild(grid);

    // Preview text (blurred) \u2014 \u043f\u043e\u043a\u0430\u0437\u044b\u0432\u0430\u0435\u043c \u0434\u0435\u0442\u0430\u043b\u0438 \u0431\u043b\u043e\u043a\u043e\u0432
    var previewText = [
      (report.admission      && report.admission.details),
      (report.demonetization && report.demonetization.details),
      (report.copyright      && report.copyright.details),
    ].filter(Boolean).join(' ');
    if (!previewText) {
      previewText = '\u0414\u0435\u0442\u0430\u043b\u044c\u043d\u044b\u0439 \u0430\u043d\u0430\u043b\u0438\u0437 \u0434\u043e\u043f\u0443\u0441\u043a\u0430 \u043a \u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438, \u0440\u0438\u0441\u043a\u043e\u0432 \u0434\u0435\u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438 \u0438 \u0430\u0432\u0442\u043e\u0440\u0441\u043a\u0438\u0445 \u043f\u0440\u0430\u0432. \u0421\u0438\u0433\u043d\u0430\u043b\u044b, \u043a\u0440\u0438\u0442\u0435\u0440\u0438\u0438 \u0438 \u043f\u043e\u0448\u0430\u0433\u043e\u0432\u044b\u0435 \u0440\u0435\u043a\u043e\u043c\u0435\u043d\u0434\u0430\u0446\u0438\u0438 \u0430\u0432\u0442\u043e\u0440\u0443 \u043a\u0430\u043d\u0430\u043b\u0430...';
    }

    var wrap    = h('div', { class: 'pw-blur-wrap' });
    var content = h('div', { class: 'pw-blur-content' }, previewText);
    wrap.appendChild(content);

    var gate     = h('div', { class: 'pw-blur-gate' });
    var gateText = h('div', { class: 'pw-blur-gate-text' }, '\u0414\u0435\u0442\u0430\u043b\u044c\u043d\u044b\u0439 \u0440\u0430\u0437\u0431\u043e\u0440 \u0438 \u0440\u0435\u043a\u043e\u043c\u0435\u043d\u0434\u0430\u0446\u0438\u0438 \u0441\u043a\u0440\u044b\u0442\u044b');

    var unlockInfo = (report.unlock_info) || (store && store.unlockInfo) || {};
    var balance    = Number(unlockInfo.balance || 0);
    var btnText    = '\u041e\u0442\u043a\u0440\u044b\u0442\u044c \u043f\u043e\u043b\u043d\u044b\u0439 \u043e\u0442\u0447\u0451\u0442 \u2014 $2.00';
    if (balance > 0) {
      btnText = '\u041e\u0442\u043a\u0440\u044b\u0442\u044c \u043f\u043e\u043b\u043d\u044b\u0439 \u043e\u0442\u0447\u0451\u0442 \u2014 $2.00 (\u0431\u0430\u043b\u0430\u043d\u0441: $' + balance.toFixed(2) + ')';
    } else if (unlockInfo.credit_available) {
      btnText = '\u041f\u043e\u043b\u0443\u0447\u0438\u0442\u044c \u043e\u0442\u0447\u0451\u0442 (\u0431\u0435\u0441\u043f\u043b\u0430\u0442\u043d\u043e)';
    }

    var errMsg = h('div', { class: 'pw-unlock-error', style: 'display:none' });
    var btn = h('button', { class: 'pw-unlock-btn' }, btnText);

    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = '\u041e\u043f\u043b\u0430\u0442\u0430...';
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
          var msg = (err && err.message) ? err.message : '\u041e\u0448\u0438\u0431\u043a\u0430 \u043f\u0440\u0438 \u043e\u043f\u043b\u0430\u0442\u0435. \u041f\u043e\u043f\u0440\u043e\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.';
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
    body.appendChild(h('div', { style: 'font-size:11px;color:#ccc;text-align:center' }, '\u0414\u0435\u0442\u0430\u043b\u044c\u043d\u044b\u0439 \u0440\u0430\u0437\u0431\u043e\u0440 \u043a\u0430\u0436\u0434\u043e\u0433\u043e \u0441\u0438\u0433\u043d\u0430\u043b\u0430 \u00b7 \u041a\u043e\u043d\u043a\u0440\u0435\u0442\u043d\u044b\u0435 \u0440\u0435\u043a\u043e\u043c\u0435\u043d\u0434\u0430\u0446\u0438\u0438 \u0430\u0432\u0442\u043e\u0440\u0443'));
    card.appendChild(body);
    return card;
  }

  // \u2500\u2500 \u0421\u0442\u0440\u043e\u043a\u0430 \u043a\u0440\u0438\u0442\u0435\u0440\u0438\u044f (\u0411\u043b\u043e\u043a 1) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
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

  // \u2500\u2500 \u0411\u043b\u043e\u043a reused content (\u0432\u044b\u0441\u043e\u043a\u0438\u0439 \u0443\u0440\u043e\u0432\u0435\u043d\u044b) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function buildReusedBox(signals) {
    var box = h('div', { class: 'pw-reused-box' });
    var highCount = signals.filter(function (s) { return s.level === 'high'; }).length;
    var title = h('div', { class: 'pw-reused-title' });
    title.innerHTML = ICONS.warn + ' Reused / Mass-produced \u043a\u043e\u043d\u0442\u0435\u043d\u0442 \u2014 ' + signals.length +
      ' \u0441\u0438\u0433\u043d\u0430\u043b' + (signals.length === 1 ? '' : signals.length < 5 ? '\u0430' : '\u043e\u0432') + ' \u0443\u0440\u043e\u0432\u043d\u044f ' +
      (highCount >= 2 ? '\u0412\u044b\u0441\u043e\u043a\u043e\u0433\u043e' : '\u0421\u0440\u0435\u0434\u043d\u0435\u0433\u043e');
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

  // \u2500\u2500 \u0421\u0442\u0440\u043e\u043a\u0430 \u0440\u0438\u0441\u043a\u0430 (\u0411\u043b\u043e\u043a\u0438 2/3) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
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

  // \u2500\u2500 \u0420\u0435\u043a\u043e\u043c\u0435\u043d\u0434\u0430\u0446\u0438\u0438 \u0434\u043b\u044f \u0430\u0432\u0442\u043e\u0440\u0430 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function buildRecommendations(recs) {
    if (!Array.isArray(recs) || !recs.length) return null;
    var section = h('div', { class: 'pw-recs-section' });
    section.appendChild(h('div', { class: 'pw-recs-title' }, '\u0420\u0435\u043a\u043e\u043c\u0435\u043d\u0434\u0430\u0446\u0438\u0438 \u0430\u0432\u0442\u043e\u0440\u0443 \u043a\u0430\u043d\u0430\u043b\u0430'));
    recs.forEach(function (rec, i) {
      var item = h('div', { class: 'pw-rec-item' });
      item.appendChild(h('div', { class: 'pw-rec-num' }, String(i + 1)));
      item.appendChild(h('div', { class: 'pw-rec-text' }, rec));
      section.appendChild(item);
    });
    return section;
  }

  // \u2500\u2500 \u041e\u0431\u044a\u0435\u0434\u0438\u043d\u0435\u043d\u0438\u0435 \u0441\u0438\u0433\u043d\u0430\u043b\u043e\u0432 \u0411\u043b\u043e\u043a 2 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  // PHP-\u0441\u0438\u0433\u043d\u0430\u043b\u044b (type, level, title, detail) + AI-\u0441\u0438\u0433\u043d\u0430\u043b\u044b (level, title, description, recommendation)
  function mergeB2Signals(full) {
    var phpSigs = (full && Array.isArray(full.php_signals)   ? full.php_signals   : []);
    var aiSigs  = (full && Array.isArray(full.block2_signals) ? full.block2_signals : []);
    // \u041d\u043e\u0440\u043c\u0430\u043b\u0438\u0437\u0443\u0435\u043c php_signals: \u0434\u043e\u0431\u0430\u0432\u043b\u044f\u0435\u043c \u043f\u043e\u043b\u0435 description (\u0441\u0438\u043d\u043e\u043d\u0438\u043c detail)
    var phpNorm = phpSigs.map(function (s) {
      return { level: s.level || 'medium', title: s.title || '', description: s.detail || '', recommendation: s.recommendation || null };
    });
    return phpNorm.concat(aiSigs);
  }

  // \u2500\u2500 \u041f\u043e\u043b\u043d\u044b\u0439 \u043e\u0442\u0447\u0451\u0442 (\u043e\u043f\u043b\u0430\u0447\u0435\u043d) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function buildFullReport(report, full) {
    var wrap = h('div', { class: 'pw-card' });

    // \u2500\u2500 \u041f\u043e\u043b\u0443\u0447\u0430\u0435\u043c \u0434\u0430\u043d\u043d\u044b\u0435 \u043f\u043e \u043a\u0430\u0436\u0434\u043e\u043c\u0443 \u0431\u043b\u043e\u043a\u0443 \u2500\u2500
    var criteria = (full && Array.isArray(full.block1_criteria) ? full.block1_criteria : null);
    var b2Sigs   = mergeB2Signals(full);
    var b3Sigs   = (full && Array.isArray(full.block3_signals) ? full.block3_signals : null);
    var recs     = (full && Array.isArray(full.recommendations_for_user) ? full.recommendations_for_user : null);
    var summaryMod = (full && full.summary_for_moderator) || report.summary || null;

    // \u2500\u2500 \u0420\u0438\u0441\u043a\u0438 \u0434\u043b\u044f \u0437\u0430\u0433\u043e\u043b\u043e\u0432\u043a\u043e\u0432 \u0432\u043a\u043b\u0430\u0434\u043e\u043aP\u2500\u2500
    var b1Risk = (report.admission      && report.admission.risk)      || 'ok';
    var b2Risk = (report.demonetization && report.demonetization.risk) || 'low';
    var b3Risk = (report.copyright      && report.copyright.risk)      || 'low';

    var tabDefs = [
      { label: '\u0411\u043b\u043e\u043a 1 \u00b7 \u0414\u043e\u043f\u0443\u0441\u043a',        risk: b1Risk, panelTitle: '\u041e\u0431\u044f\u0437\u0430\u0442\u0435\u043b\u044c\u043d\u044b\u0435 \u043a\u0440\u0438\u0442\u0435\u0440\u0438\u0438',      type: 'criteria',  data: criteria },
      { label: '\u0411\u043b\u043e\u043a 2 \u00b7 \u0414\u0435\u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u044f', risk: b2Risk, panelTitle: '\u0420\u0438\u0441\u043a\u0438 \u0434\u0435\u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438',        type: 'signals2',  data: b2Sigs   },
      { label: '\u0411\u043b\u043e\u043a 3 \u00b7 \u0421\u0442\u0440\u0430\u0439\u043a\u0438',       risk: b3Risk, panelTitle: '\u0420\u0438\u0441\u043a\u0438 \u0430\u0432\u0442\u043e\u0440\u0441\u043a\u0438\u0445 \u043f\u0440\u0430\u0432',       type: 'signals3',  data: b3Sigs   },
    ];

    // \u2500\u2500 Tab row \u2500\u2500
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

    // \u2500\u2500 \u041f\u0430\u043d\u0435\u043b\u0438 \u2500\u2500
    tabDefs.forEach(function (td, i) {
      var panel = h('div', { class: 'pw-tab-panel', style: i === 0 ? '' : 'display:none' });

      // \u041f\u043e\u0434\u0437\u0430\u0433\u043e\u043b\u043e\u0432\u043e\u043a \u0441 \u0431\u0435\u0439\u0434\u0436\u0435\u043c \u0440\u0438\u0441\u043a\u0430
      var phdr = h('div', { style: 'display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:6px' });
      phdr.appendChild(h('div', { style: 'font-size:13px;font-weight:500;color:#1a1a1a' }, td.panelTitle));
      phdr.innerHTML += badge(td.risk);
      panel.appendChild(phdr);

      if (td.type === 'criteria') {
        // \u0411\u043b\u043e\u043a 1: \u0441\u043f\u0438\u0441\u043e\u043a \u043a\u0440\u0438\u0442\u0435\u0440\u0438\u0435\u0432
        if (criteria && criteria.length) {
          var crList = h('div', { class: 'pw-cr-list' });
          criteria.forEach(function (c) { crList.appendChild(buildCriteriaRow(c)); });
          panel.appendChild(crList);
        } else if (report.admission && report.admission.details) {
          panel.appendChild(h('div', { style: 'font-size:12px;line-height:1.7;color:#555' }, report.admission.details));
        } else {
          panel.appendChild(h('p', { style: 'font-size:12px;color:#aaa' }, '\u0414\u0430\u043d\u043d\u044b\u0435 \u0431\u043b\u043e\u043a\u0430 \u043d\u0435 \u043e\u0431\u043d\u0430\u0440\u0443\u0436\u0435\u043d\u044b'));
        }

      } else if (td.type === 'signals2') {
        // \u0411\u043b\u043e\u043a 2: \u0432\u044b\u0441\u043e\u043a\u0438\u0435 \u0441\u0438\u0433\u043d\u0430\u043b\u044b \u0432 reused-box, \u043e\u0441\u0442\u0430\u043b\u044c\u043d\u044b\u0435 \u2014 \u043e\u0442\u0434\u0435\u043b\u044c\u043d\u043e
        if (b2Sigs.length) {
          var highSigs = b2Sigs.filter(function (s) { return s.level === 'high'; });
          var otherSigs = b2Sigs.filter(function (s) { return s.level !== 'high'; });

          if (highSigs.length >= 2) {
            panel.appendChild(buildReusedBox(highSigs));
          } else if (highSigs.length === 1) {
            // \u041e\u0434\u0438\u043d \u0432\u044b\u0441\u043e\u043a\u0438\u0439 \u2014 \u0442\u043e\u0436\u0435 \u043f\u043e\u043a\u0430\u0437\u044b\u0432\u0430\u0435\u043c \u0432 reused-box
            panel.appendChild(buildReusedBox(highSigs));
          }

          if (otherSigs.length) {
            var sectTitle = h('div', { class: 'pw-risk-section-title' }, '\u0414\u043e\u043f\u043e\u043b\u043d\u0438\u0442\u0435\u043b\u044c\u043d\u044b\u0435 \u0441\u0438\u0433\u043d\u0430\u043b\u044b');
            panel.appendChild(sectTitle);
            otherSigs.forEach(function (sig) { panel.appendChild(buildRiskRow(sig)); });
          }

          // \u0415\u0441\u043b\u0438 \u0442\u043e\u043b\u044c\u043a\u043e \u0441\u0440\u0435\u0434\u043d\u0438\u0435 \u0441\u0438\u0433\u043d\u0430\u043b\u044b (\u043d\u0435\u0442 \u0432\u044b\u0441\u043e\u043a\u0438\u0445)
          if (!highSigs.length && !otherSigs.length) {
            panel.appendChild(h('p', { style: 'font-size:12px;color:#aaa' }, '\u0421\u0438\u0433\u043d\u0430\u043b\u044b \u0434\u0435\u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438 \u043d\u0435 \u043e\u0431\u043d\u0430\u0440\u0443\u0436\u0435\u043d\u044b'));
          }
        } else if (report.demonetization && report.demonetization.details) {
          panel.appendChild(h('div', { style: 'font-size:12px;line-height:1.7;color:#555' }, report.demonetization.details));
        } else {
          panel.appendChild(h('p', { style: 'font-size:12px;color:#16a34a' }, '\u0417\u043d\u0430\u0447\u0438\u043c\u044b\u0445 \u0441\u0438\u0433\u043d\u0430\u043b\u043e\u0432 \u0434\u0435\u043c\u043e\u043d\u0435\u0442\u0438\u0437\u0430\u0446\u0438\u0438 \u043d\u0435 \u043e\u0431\u043d\u0430\u0440\u0443\u0436\u0435\u043d\u043e'));
        }

      } else if (td.type === 'signals3') {
        // \u0411\u043b\u043e\u043a 3: \u0440\u0438\u0441\u043a\u0438 \u0441\u0442\u0440\u0430\u0439\u043a\u043e\u0432
        if (b3Sigs && b3Sigs.length) {
          b3Sigs.forEach(function (sig) { panel.appendChild(buildRiskRow(sig)); });
        } else if (report.copyright && report.copyright.details) {
          panel.appendChild(h('div', { style: 'font-size:12px;line-height:1.7;color:#555' }, report.copyright.details));
        } else {
          panel.appendChild(h('p', { style: 'font-size:12px;color:#16a34a' }, '\u0417\u043d\u0430\u0447\u0438\u043c\u044b\u0445 \u0440\u0438\u0441\u043a\u043e\u0432 \u0430\u0432\u0442\u043e\u0440\u0441\u043a\u0438\u0445 \u043f\u0440\u0430\u0432 \u043d\u0435 \u043e\u0431\u043d\u0430\u0440\u0443\u0436\u0435\u043d\u043e'));
        }
      }

      panels.push(panel);
      wrap.appendChild(panel);
    });

    // \u2500\u2500 \u0418\u0442\u043e\u0433 \u0434\u043b\u044f \u043c\u043e\u0434\u0435\u0440\u0430\u0442\u043e\u0440\u0430 \u2500\u2500
    if (summaryMod) {
      var note = h('div', { class: 'pw-flag-note' });
      note.innerHTML = '<strong>\u0418\u0442\u043e\u0433 \u0434\u043b\u044f \u043c\u043e\u0434\u0435\u0440\u0430\u0442\u043e\u0440\u0430:</strong> ' + summaryMod;
      wrap.appendChild(note);
    }

    // \u2500\u2500 \u0420\u0435\u043a\u043e\u043c\u0435\u043d\u0434\u0430\u0446\u0438\u0438 \u0434\u043b\u044f \u0430\u0432\u0442\u043e\u0440\u0430 \u2500\u2500
    var recsEl = buildRecommendations(recs);
    if (recsEl) wrap.appendChild(recsEl);

    // \u2500\u2500 \u041a\u043d\u043e\u043f\u043a\u0430 \u2500\u2500
    var actRow = h('div', { class: 'pw-action-row' });
    var btnNew = h('button', { class: 'pw-btn pw-btn-ghost' }, '\u041f\u0440\u043e\u0432\u0435\u0440\u0438\u0442\u044c \u0434\u0440\u0443\u0433\u043e\u0439 \u043a\u0430\u043d\u0430\u043b');
    btnNew.addEventListener('click', function () {
      removeInject();
      var st = getStore();
      if (st) { st.status = null; st.report = null; st.auditId = null; }
    });
    actRow.appendChild(btnNew);
    wrap.appendChild(actRow);

    return wrap;
  }

  // \u2500\u2500 \u0413\u043b\u0430\u0432\u043d\u0430\u044f \u0444\u0443\u043d\u043a\u0446\u0438\u044f \u0440\u0435\u043d\u0434\u0435\u0440\u0430 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function removeInject() {
    var el = document.getElementById('pw-audit-inject');
    if (el) el.remove();
    var ar = document.querySelector('.audit-result');
    if (ar) ar.style.display = '';
    var ub = document.querySelector('.audit-unlock-button');
    if (ub) ub.style.display = '';
  }

  // \u2500\u2500 \u041a\u0435\u0448 \u0438 \u0437\u0430\u0433\u0440\u0443\u0437\u043a\u0430 \u043f\u043e\u043b\u043d\u044b\u0445 \u0434\u0430\u043d\u043d\u044b\u0445 \u0430\u0443\u0434\u0438\u0442\u0430 \u0438\u0437 REST API \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
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

    // \u0411\u043e\u0433\u0430\u0442\u044b\u0435 \u0434\u0430\u043d\u043d\u044b\u0435: \u0441\u043d\u0430\u0447\u0430\u043b\u0430 \u0438\u0437 apiData (\u043f\u0440\u044f\u043c\u043e\u0439 fetch), \u043f\u043e\u0442\u043e\u043c \u0438\u0437 store
    var full    = (_apiData && _apiData.full)    || store.full    || store.reportFull || null;
    var preview = (_apiData && _apiData.preview) || store.preview || null;

    // 1. \u0412\u0435\u0440\u0434\u0438\u043a\u0442
    inject.appendChild(buildVerdictBanner(report));

    // 2. \u0422\u0440\u0438 \u0431\u043b\u043e\u043a\u0430-\u043a\u0430\u0440\u0442\u043e\u0447\u043a\u0438
    inject.appendChild(buildBlocksRow(report));

    // 3. \u041e\u0441\u043d\u043e\u0432\u043d\u043e\u0439 \u043a\u043e\u043d\u0442\u0435\u043d\u0442
    var isPaid = store.isPaid || (report && report.is_paid);

    var hasApiData = _apiData && _apiData.full;
    if (isPaid && !full && !hasApiData && store.auditId) {
      // \u0414\u0430\u043d\u043d\u044b\u0435 \u0435\u0449\u0451 \u043d\u0435 \u0437\u0430\u0433\u0440\u0443\u0436\u0435\u043d\u044b \u2014 fetches API \u0438 \u043f\u0435\u0440\u0435\u0440\u0435\u043d\u0434\u0435\u0440\u0438\u0442
      inject.appendChild(buildPreviewCard(report, store));
      fetchAuditFull(store.auditId, function (apiData) {
        renderReport(store, apiData || {});
      });
    } else {
      inject.appendChild(isPaid ? buildFullReport(report, full) : buildPreviewCard(report, store));
    }

    // \u0421\u043a\u0440\u044b\u0432\u0430\u0435\u043c \u043e\u0440\u0438\u0433\u0438\u043d\u0430\u043b\u044c\u043d\u044b\u0435 Vue-\u0441\u0435\u043a\u0446\u0438\u0438
    auditResult.style.display = 'none';
    var unlockDiv = document.querySelector('.audit-unlock-button');
    if (unlockDiv) unlockDiv.style.display = 'none';
  }

  // \u2500\u2500 \u0426\u0438\u043a\u043b \u043e\u043f\u0440\u043e\u0441\u0430 store \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  function tryRender(attempts) {
    if (attempts <= 0) return;
    var store = getStore();
    if (!store) {
      setTimeout(function () { tryRender(attempts - 1); }, 400);
      return;
    }

    if (store.status === 'done' && store.report) {
      renderReport(store);
    }

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
          removeInject();
        }
      }

      if (!document.getElementById('pw-audit-inject') && s.status === 'done' && s.report) {
        renderReport(s);
      }
    }, 800);
  }

  // \u2500\u2500 \u0421\u0442\u0430\u0440\u0444 \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(function () { tryRender(30); }, 600);
    });
  } else {
    setTimeout(function () { tryRender(30); }, 600);
  }

})();
