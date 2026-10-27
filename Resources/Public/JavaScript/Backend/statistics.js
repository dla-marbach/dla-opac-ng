/**
 * Backend-Modul »Katalog-Statistik«: lädt die aggregierten JSON-Daten und stellt sie ohne Bibliotheken dar
 * (Kennzahlen, Verlauf als SVG, Tagesprofil, Tabellen mit CSS-Balken und CSV-Download).
 *
 * Alle Werte aus den Daten (Suchbegriffe, Facettenwerte, IDs) werden ausschließlich per textContent
 * ausgegeben, nie als HTML.
 */

const root = document.getElementById('dla-opac-stats');

const PAGE_AREAS = ['start', 'search', 'detail', 'collection', 'classification'];
const SERIES_AREAS = ['search', 'detail', 'collection', 'classification', 'start'];
const ROWS_COLLAPSED = 15;

const SECTIONS = [
  {
    id: 'overview',
    tables: [
      { id: 'areas', rows: (m) => areaRows(m) },
      { id: 'lang', rows: (m) => sumDimension(m, PAGE_AREAS, 'lang') },
      { id: 'ref', rows: (m) => sumDimension(m, PAGE_AREAS, 'ref') },
    ],
  },
  {
    id: 'search',
    area: 'search',
    tables: [
      { id: 'fields' }, { id: 'facets' }, { id: 'facetValues', facetSelect: true }, { id: 'results', ordered: true },
      { id: 'pages', ordered: true }, { id: 'filters', ordered: true }, { id: 'mode' }, { id: 'form' },
      { id: 'sort' }, { id: 'count' }, { id: 'entry' }, { id: 'terms', terms: true }, { id: 'zeroTerms', terms: true },
    ],
  },
  {
    id: 'detail',
    area: 'detail',
    tables: [{ id: 'source' }, { id: 'found' }, { id: 'format' }, { id: 'records', records: true }, { id: 'entry' }],
  },
  {
    id: 'collection',
    area: 'collection',
    tables: [{ id: 'mode' }, { id: 'terms', terms: true }],
  },
  {
    id: 'classification',
    area: 'classification',
    tables: [{ id: 'mode' }, { id: 'terms', terms: true }],
  },
  {
    id: 'ajax',
    tables: [
      { id: 'ajaxAreas', rows: (m) => Object.keys(m).filter((a) => a.startsWith('ajax:')).map((a) => [a, num(m[a].hits)]) },
      { id: 'ajaxCollection', rows: (m) => entries(m['ajax:collection']?.mode) },
      { id: 'ajaxClassification', rows: (m) => entries(m['ajax:classification']?.mode) },
    ],
  },
];

const state = {
  data: null,
  audiences: new Set(['ext', 'int']),
  facet: null,
};

function t(key, fallback) {
  const labels = (window.TYPO3 && window.TYPO3.lang) || {};
  return labels[key] || (fallback !== undefined ? fallback : key);
}

function el(tag, attributes = {}, ...children) {
  const node = document.createElement(tag);
  for (const [name, value] of Object.entries(attributes)) {
    if (name === 'class') {
      node.className = value;
    } else if (name === 'text') {
      node.textContent = value;
    } else if (name.startsWith('on')) {
      node.addEventListener(name.slice(2), value);
    } else if (value !== undefined && value !== null && value !== false) {
      node.setAttribute(name, value === true ? '' : value);
    }
  }
  for (const child of children) {
    if (child !== null && child !== undefined) {
      node.append(child);
    }
  }
  return node;
}

function svg(tag, attributes = {}) {
  const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
  for (const [name, value] of Object.entries(attributes)) {
    node.setAttribute(name, value);
  }
  return node;
}

function num(value) {
  return Number(value) || 0;
}

function entries(map) {
  return map ? Object.entries(map).map(([key, value]) => [key, num(value)]) : [];
}

function formatNumber(value) {
  return new Intl.NumberFormat(document.documentElement.lang || 'de').format(value);
}

function formatPercent(part, total) {
  if (!total) {
    return '–';
  }
  return new Intl.NumberFormat(document.documentElement.lang || 'de', { style: 'percent', maximumFractionDigits: 1 }).format(part / total);
}

function isoDate(date) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** Zählerstände rekursiv addieren (wie StatisticsAggregator::mergeCounts). */
function mergeCounts(target, source) {
  for (const [key, value] of Object.entries(source || {})) {
    if (value !== null && typeof value === 'object') {
      if (typeof target[key] !== 'object' || target[key] === null) {
        target[key] = {};
      }
      mergeCounts(target[key], value);
    } else if (typeof value === 'number') {
      target[key] = num(target[key]) + value;
    }
  }
  return target;
}

/** Bereiche der gewählten Zielgruppen (extern, intern, Bots) zusammenfassen. */
function mergedAreas() {
  const merged = {};
  for (const [audience, areas] of Object.entries(state.data.audiences || {})) {
    if (!state.audiences.has(audience)) {
      continue;
    }
    for (const [area, stats] of Object.entries(areas)) {
      merged[area] = mergeCounts(merged[area] || {}, stats);
    }
  }
  return merged;
}

function areaRows(merged) {
  return Object.keys(merged).filter((area) => !area.startsWith('ajax:')).map((area) => [area, num(merged[area].hits)]);
}

function sumDimension(merged, areas, dimension) {
  const sum = {};
  for (const area of areas) {
    mergeCounts(sum, merged[area]?.[dimension]);
  }
  return entries(sum);
}

function valueLabel(dimension, key) {
  if (key === '_other') {
    return t('value._other', 'Other');
  }
  if (dimension === 'areas' || dimension === 'ajaxAreas') {
    return t(`area.${key}`, key);
  }
  if (dimension === 'entry') {
    return `${t('value.page', 'Page')} ${key}`;
  }
  if (dimension === 'facetValues' && key.startsWith('not:')) {
    return `${t('value.not', 'not')} ${key.slice(4)}`;
  }
  return t(`value.${dimension}.${key}`, key);
}

// ----------------------------------------------------------------------------------------------------------------
// Darstellung

function renderTiles(container, merged) {
  const pageViews = PAGE_AREAS.reduce((sum, area) => sum + num(merged[area]?.hits), 0);
  const results = merged.search?.results || {};
  const resultsTotal = Object.values(results).reduce((sum, value) => sum + num(value), 0);
  const pow = state.data.pow || {};

  const tiles = [
    ['tile.pageViews', formatNumber(pageViews)],
    ['tile.searches', formatNumber(num(merged.search?.hits))],
    ['tile.newSearches', formatNumber(num(merged.search?.pages?.['1']))],
    ['tile.zeroResults', formatPercent(num(results['0']), resultsTotal)],
    ['tile.details', formatNumber(num(merged.detail?.hits))],
    ['tile.collection', formatNumber(num(merged.collection?.hits))],
    ['tile.classification', formatNumber(num(merged.classification?.hits))],
    ['tile.powSolved', formatNumber(num(pow.solved))],
    ['tile.powIssued', formatNumber(num(pow.issued))],
  ];

  container.replaceChildren(...tiles.map(([key, value]) => el('div', { class: 'dla-stats-tile' },
    el('div', { class: 'dla-stats-tile-value', text: value }),
    el('div', { class: 'dla-stats-tile-label', text: t(key) }),
  )));
}

function renderSeries(container) {
  const series = state.data.series || [];
  const width = 800;
  const height = 220;
  const padding = { top: 12, right: 12, bottom: 28, left: 56 };
  const values = SERIES_AREAS.map((area) => series.map((point) => {
    let sum = 0;
    for (const audience of state.audiences) {
      sum += num(point.hits?.[audience]?.[area]);
    }
    return sum;
  }));
  const max = Math.max(1, ...values.flat());
  const x = (i) => padding.left + (series.length > 1 ? i * (width - padding.left - padding.right) / (series.length - 1) : (width - padding.left - padding.right) / 2);
  const y = (v) => padding.top + (height - padding.top - padding.bottom) * (1 - v / max);

  const chart = svg('svg', { viewBox: `0 0 ${width} ${height}`, class: 'dla-stats-chart', role: 'img', 'aria-label': t('chart.series') });
  for (const fraction of [0, 0.5, 1]) {
    const value = Math.round(max * fraction);
    chart.append(svg('line', { x1: padding.left, x2: width - padding.right, y1: y(value), y2: y(value), class: 'dla-stats-grid' }));
    const label = svg('text', { x: padding.left - 6, y: y(value) + 4, class: 'dla-stats-axis', 'text-anchor': 'end' });
    label.textContent = formatNumber(value);
    chart.append(label);
  }
  const labelIndexes = new Set([0, Math.floor((series.length - 1) / 2), series.length - 1]);
  series.forEach((point, i) => {
    if (labelIndexes.has(i)) {
      const label = svg('text', { x: x(i), y: height - 8, class: 'dla-stats-axis', 'text-anchor': i === 0 ? 'start' : (i === series.length - 1 ? 'end' : 'middle') });
      label.textContent = point.label;
      chart.append(label);
    }
  });
  SERIES_AREAS.forEach((area, index) => {
    if (!values[index].some((v) => v > 0)) {
      return;
    }
    const points = values[index].map((v, i) => `${x(i).toFixed(1)},${y(v).toFixed(1)}`).join(' ');
    chart.append(svg('polyline', { points, class: `dla-stats-line dla-stats-color-${index}` }));
    values[index].forEach((v, i) => {
      const dot = svg('circle', { cx: x(i), cy: y(v), r: 2.5, class: `dla-stats-dot dla-stats-color-${index}` });
      const title = svg('title');
      title.textContent = `${t(`area.${area}`, area)} ${series[i].label}: ${formatNumber(v)}`;
      dot.append(title);
      chart.append(dot);
    });
  });

  const legend = el('ul', { class: 'dla-stats-legend' }, ...SERIES_AREAS.map((area, index) => el('li', {},
    el('span', { class: `dla-stats-swatch dla-stats-color-${index}` }),
    t(`area.${area}`, area),
  )));

  container.replaceChildren(chart, legend);
}

function renderHours(container, merged) {
  const hours = new Array(24).fill(0);
  for (const area of PAGE_AREAS) {
    for (const [hour, value] of Object.entries(merged[area]?.hours || {})) {
      hours[Number(hour)] += num(value);
    }
  }
  const max = Math.max(1, ...hours);
  if (state.data.granularity !== 'day') {
    container.replaceChildren(el('p', { class: 'dla-stats-note', text: t('note.hoursDailyOnly') }));
    return;
  }
  container.replaceChildren(el('div', { class: 'dla-stats-hours' }, ...hours.map((value, hour) => el('div', {
    class: 'dla-stats-hour',
    title: `${hour}:00–${hour}:59: ${formatNumber(value)}`,
  },
  el('span', { class: 'dla-stats-hour-bar', style: `height:${(value / max * 100).toFixed(1)}%` }),
  el('span', { class: 'dla-stats-hour-label', text: String(hour) }),
  ))));
}

function sortRows(rows, ordered) {
  if (ordered) {
    return rows;
  }
  return rows.sort((a, b) => {
    if (a[0] === '_other') return 1;
    if (b[0] === '_other') return -1;
    return b[1] - a[1] || String(a[0]).localeCompare(String(b[0]));
  });
}

function downloadCsv(name, rows) {
  const escape = (value) => `"${String(value).replaceAll('"', '""')}"`;
  const lines = [[t('column.value'), t('column.count')].map(escape).join(';')]
    .concat(rows.map(([label, count]) => [escape(label), count].join(';')));
  const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
  const link = el('a', { href: URL.createObjectURL(blob), download: `${name}_${state.data.from}_${state.data.to}.csv` });
  document.body.append(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(link.href);
}

function renderTable(sectionId, definition, merged) {
  const area = SECTIONS.find((section) => section.id === sectionId)?.area;
  const stats = area ? merged[area] || {} : merged;
  let rows;
  let facetSelect = null;

  if (definition.rows) {
    rows = definition.rows(merged);
  } else if (definition.facetSelect) {
    const facets = Object.keys(stats.facetValues || {}).sort();
    if (!facets.includes(state.facet)) {
      state.facet = facets[0] || null;
    }
    facetSelect = facets.length === 0 ? null : el('select', {
      class: 'form-select form-select-sm',
      'aria-label': t('table.facetValues'),
      onchange: (event) => { state.facet = event.target.value; render(); },
    }, ...facets.map((facet) => el('option', { value: facet, selected: facet === state.facet, text: facet })));
    rows = entries(stats.facetValues?.[state.facet]);
  } else {
    rows = entries(stats[definition.id]);
  }

  rows = sortRows(rows.filter(([, count]) => count > 0), definition.ordered);
  const total = rows.reduce((sum, [, count]) => sum + count, 0);
  const csvRows = rows.map(([key, count]) => [valueLabel(definition.id, String(key)), count]);
  const title = t(`table.${definition.id}`);
  const card = el('section', { class: 'dla-stats-card' });

  card.append(el('header', { class: 'dla-stats-card-header' },
    el('h3', { text: title }),
    facetSelect,
    rows.length ? el('button', {
      type: 'button',
      class: 'btn btn-default btn-sm',
      text: 'CSV',
      title: t('action.csv'),
      onclick: () => downloadCsv(`${sectionId}-${definition.id}`, csvRows),
    }) : null,
  ));

  if (definition.terms) {
    card.append(el('p', { class: 'dla-stats-note', text: t('note.termsThreshold').replace('%d', state.data.termsMinCount) }));
  }

  if (!rows.length) {
    card.append(el('p', { class: 'dla-stats-empty', text: t('note.noData') }));
    return card;
  }

  const max = Math.max(...rows.map(([key, count]) => (key === '_other' ? 0 : count)), 1);
  const tbody = el('tbody');
  rows.forEach(([key, count], index) => {
    const label = valueLabel(definition.id, String(key));
    let labelNode = document.createTextNode(label);
    if (definition.records && key !== '_other') {
      labelNode = el('a', { href: `/find/opac/id/${encodeURIComponent(key)}`, target: '_blank', rel: 'noopener', text: label });
    }
    tbody.append(el('tr', { class: index >= ROWS_COLLAPSED ? 'dla-stats-row-more' : null },
      el('td', { class: 'dla-stats-label' }, labelNode),
      el('td', { class: 'dla-stats-num', text: formatNumber(count) }),
      el('td', { class: 'dla-stats-share' },
        el('span', { class: 'dla-stats-bar', style: `width:${(key === '_other' ? 0 : count / max * 100).toFixed(1)}%` }),
        el('span', { class: 'dla-stats-percent', text: formatPercent(count, total) }),
      ),
    ));
  });

  const table = el('table', { class: 'dla-stats-table' },
    el('thead', {}, el('tr', {},
      el('th', { scope: 'col', text: t('column.value') }),
      el('th', { scope: 'col', class: 'dla-stats-num', text: t('column.count') }),
      el('th', { scope: 'col', text: t('column.share') }),
    )),
    tbody,
  );
  card.append(table);

  if (rows.length > ROWS_COLLAPSED) {
    card.append(el('button', {
      type: 'button',
      class: 'btn btn-link btn-sm dla-stats-more',
      text: t('action.showAll').replace('%d', formatNumber(rows.length)),
      onclick: (event) => { table.classList.add('dla-stats-expanded'); event.target.remove(); },
    }));
  }

  return card;
}

function render() {
  if (!state.data) {
    return;
  }
  const merged = mergedAreas();

  root.querySelector('[data-range-info]').textContent = state.data.granularity === 'month'
    ? t('note.monthly').replace('%s', state.data.from).replace('%s', state.data.to)
    : `${state.data.from} – ${state.data.to}`;

  renderTiles(root.querySelector('[data-tiles]'), merged);
  renderSeries(root.querySelector('[data-series]'));
  renderHours(root.querySelector('[data-hours]'), merged);

  const sections = root.querySelector('[data-sections]');
  sections.replaceChildren(...SECTIONS.map((section) => el('section', { class: 'dla-stats-section' },
    el('h2', { text: t(`section.${section.id}`) }),
    el('div', { class: 'dla-stats-grid-cards' }, ...section.tables.map((definition) => renderTable(section.id, definition, merged))),
  )));
}

async function load(from, to) {
  const status = root.querySelector('[data-status]');
  status.textContent = t('status.loading');
  const url = new URL(root.dataset.url, window.location.href);
  url.searchParams.set('from', from);
  url.searchParams.set('to', to);
  try {
    const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    if (!response.ok) {
      throw new Error(String(response.status));
    }
    state.data = await response.json();
    status.textContent = '';
    render();
  } catch (error) {
    status.textContent = `${t('status.error')} (${error.message})`;
  }
}

function presetRange(preset) {
  const today = new Date();
  const from = new Date(today);
  switch (preset) {
    case '7d': from.setDate(today.getDate() - 6); break;
    case '30d': from.setDate(today.getDate() - 29); break;
    case '90d': from.setDate(today.getDate() - 89); break;
    case '12m': from.setMonth(today.getMonth() - 11, 1); break;
    case 'year': from.setMonth(0, 1); break;
    default: break;
  }
  return [isoDate(from), isoDate(today)];
}

function init() {
  if (!root) {
    return;
  }
  const form = root.querySelector('[data-controls]');
  const fromInput = form.querySelector('[name="from"]');
  const toInput = form.querySelector('[name="to"]');

  const applyPreset = (preset) => {
    [fromInput.value, toInput.value] = presetRange(preset);
    load(fromInput.value, toInput.value);
  };

  form.querySelectorAll('[data-preset]').forEach((button) => {
    button.addEventListener('click', () => applyPreset(button.dataset.preset));
  });
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    load(fromInput.value, toInput.value);
  });
  form.querySelectorAll('[name="audience"]').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
      state.audiences = new Set([...form.querySelectorAll('[name="audience"]:checked')].map((input) => input.value));
      render();
    });
  });

  applyPreset('30d');
}

init();
