// === YOCTEL OMR - Professional Application ===

let API_URL = localStorage.getItem('omr_api_url') || '';
const API_AUTH = 'Basic ' + btoa('user:4a30dc0528abcf536899beaa6b8b9c6b');

// Wrapper for fetch with auth headers
function apiFetch(url, options = {}) {
  if (!options.headers) options.headers = {};
  if (typeof options.headers === 'object' && !(options.headers instanceof Headers)) {
    options.headers = Object.assign({}, options.headers, { 'Authorization': API_AUTH });
  } else if (options.headers instanceof Headers) {
    options.headers.set('Authorization', API_AUTH);
  }
  return fetch(url, options);
}
let templates = [];
let answerKeys = [];
let students = [];
let sessions = [];
let allResults = [];
let selectedFiles = [];
let currentAnswerKeyAnswers = {};
let processingAborted = false;

// === INITIALIZATION ===
document.addEventListener('DOMContentLoaded', () => {
  showTab('dashboard');
  checkConnection();
  loadAll();
});

async function checkConnection() {
  const el = document.getElementById('connectionStatus');
  try {
    const res = await apiFetch(`${API_URL}/api/settings`);
    if (res.ok) {
      el.innerHTML = '<i class="fas fa-circle" style="color:#4CAF50;font-size:8px"></i> Connected';
    } else throw new Error();
  } catch {
    el.innerHTML = '<i class="fas fa-circle" style="color:#f44336;font-size:8px"></i> Disconnected';
  }
}

async function loadAll() {
  await Promise.all([loadTemplates(), loadAnswerKeys(), loadStudents(), loadSessions(), loadDashboard()]);
}

// === TAB MANAGEMENT ===
function showTab(tabId) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
  
  const tab = document.getElementById('tab-' + tabId);
  if (tab) tab.classList.add('active');
  
  // Highlight menu
  document.querySelectorAll('.menu-item').forEach(m => {
    if (m.getAttribute('onclick')?.includes(tabId)) m.classList.add('active');
  });

  // Show relevant toolbar
  document.querySelectorAll('.toolbar-group').forEach(g => g.style.display = 'none');
  if (tabId === 'layout') document.getElementById('toolbarLayout').style.display = 'flex';
  else if (tabId === 'reader') document.getElementById('toolbarReader').style.display = 'flex';
  else document.getElementById('toolbarDashboard').style.display = 'flex';

  // Load data for specific tabs
  if (tabId === 'dashboard') loadDashboard();
  if (tabId === 'reports') populateReportSessionSelect();
}

function showReaderSubTab(name) {
  document.querySelectorAll('.reader-subtab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('subtab-' + name)?.classList.add('active');
  event.target.classList.add('active');
}

// === TOAST ===
function showToast(msg, type = 'info') {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.className = 'toast ' + type + ' show';
  setTimeout(() => toast.className = 'toast', 3500);
}

// === MODAL ===
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// === DASHBOARD ===
async function loadDashboard() {
  try {
    const res = await apiFetch(`${API_URL}/api/dashboard`);
    if (!res.ok) return;
    const data = await res.json();
    
    document.getElementById('statSessions').textContent = data.total_sessions || 0;
    document.getElementById('statScanned').textContent = data.total_scanned || 0;
    document.getElementById('statStudents').textContent = data.total_students || 0;
    document.getElementById('statKeys').textContent = data.total_answer_keys || 0;
    document.getElementById('statTemplates').textContent = data.total_templates || 0;

    // Recent sessions
    const sessEl = document.getElementById('recentSessions');
    if (data.recent_sessions && data.recent_sessions.length > 0) {
      sessEl.innerHTML = data.recent_sessions.slice(0, 5).map(s => `
        <div class="topper-item" onclick="showTab('reader')">
          <div class="topper-info">
            <div class="topper-name">${escHtml(s.name)}</div>
            <div class="topper-details">${escHtml(s.subject || '')} ${s.date ? '| ' + s.date : ''}</div>
          </div>
          <div class="topper-score">
            <div class="topper-score-value">${s.total_scanned}</div>
            <div class="topper-pct">${s.status}</div>
          </div>
        </div>
      `).join('');
    } else {
      sessEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No sessions yet</p></div>';
    }

    // Score distribution chart
    renderScoreDistChart(data.score_distribution || {}, 'chartBars');
  } catch (e) {
    console.error('Dashboard load failed:', e);
  }
}

function renderScoreDistChart(dist, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;
  const maxVal = Math.max(...Object.values(dist), 1);
  const colors = { '0-20': '#f44336', '21-40': '#FF9800', '41-60': '#FFC107', '61-80': '#2196F3', '81-100': '#4CAF50' };
  container.innerHTML = Object.entries(dist).map(([range, count]) => `
    <div class="chart-bar-row">
      <span class="chart-bar-label">${range}%</span>
      <div class="chart-bar-track">
        <div class="chart-bar-fill" style="width:${Math.max((count/maxVal)*100, count>0?8:0)}%;background:${colors[range]||'#2196F3'}">
          ${count > 0 ? count : ''}
        </div>
      </div>
    </div>
  `).join('');
}

function refreshDashboard() { loadAll(); showToast('Data refreshed', 'success'); }

// === TEMPLATES ===
async function loadTemplates() {
  try {
    const res = await apiFetch(`${API_URL}/api/templates`);
    if (!res.ok) return;
    templates = await res.json();
    renderTemplateList();
    populateTemplateSelects();
  } catch (e) { console.error('Templates load failed:', e); }
}

function renderTemplateList() {
  const list = document.getElementById('templateList');
  if (!list) return;
  list.innerHTML = templates.map(t => `
    <div class="template-item" onclick="selectTemplate('${t.id}')" id="tpl-${t.id}">
      <div class="tpl-name">${escHtml(t.name)}</div>
      <div class="tpl-info">${t.total_questions}Q | ${t.options_per_question} opts | ${t.columns} cols</div>
      <span class="tpl-badge ${t.is_builtin ? 'builtin' : 'custom'}">${t.is_builtin ? 'Built-in' : 'Custom'}</span>
      ${!t.is_builtin ? `<button class="btn-sm" style="float:right;margin-top:-20px" onclick="event.stopPropagation();deleteTemplate('${t.id}')"><i class="fas fa-trash"></i></button>` : ''}
    </div>
  `).join('');
}

// ========== TEMPLATE EDITOR ==========
// All template editor logic is in template-editor.js
// These variables are shared between app.js and template-editor.js
let canvasScale = 1.0;
let activeTemplate = null;

function showCreateTemplate() { openModal('modalCreateTemplate'); }

async function createTemplate() {
  const name = document.getElementById('tplName').value.trim();
  if (!name) { showToast('Template name required', 'error'); return; }
  
  try {
    const body = {
      name,
      total_questions: parseInt(document.getElementById('tplQuestions').value) || 75,
      options_per_question: parseInt(document.getElementById('tplOptions').value) || 4,
      columns: parseInt(document.getElementById('tplColumns').value) || 3,
      questions_per_column: parseInt(document.getElementById('tplQPerCol').value) || 25,
      fill_threshold: parseFloat(document.getElementById('tplThreshold').value) || 0.3,
      correct_marks: parseFloat(document.getElementById('tplCorrect').value) || 1,
      wrong_marks: parseFloat(document.getElementById('tplWrong').value) || 0,
      unanswered_marks: parseFloat(document.getElementById('tplUnanswered').value) || 0,
      has_barcode: document.getElementById('tplBarcode').checked,
      use_pivots: document.getElementById('tplPivots').checked,
    };
    const res = await apiFetch(`${API_URL}/api/templates`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
    });
    if (!res.ok) throw new Error(await res.text());
    showToast('Template created successfully', 'success');
    closeModal('modalCreateTemplate');
    loadTemplates();
  } catch (e) { showToast('Failed: ' + e.message, 'error'); }
}

async function deleteTemplate(id) {
  if (!confirm('Delete this template?')) return;
  try {
    const res = await apiFetch(`${API_URL}/api/templates/${id}`, { method: 'DELETE' });
    if (!res.ok) { const d = await res.json(); showToast(d.detail || 'Failed', 'error'); return; }
    showToast('Template deleted', 'info');
    loadTemplates();
  } catch (e) { showToast('Failed', 'error'); }
}

function populateTemplateSelects() {
  const selects = ['readerTemplate', 'sessionTemplate'];
  selects.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const current = el.value;
    el.innerHTML = templates.map(t => `<option value="${t.id}">${escHtml(t.name)}</option>`).join('');
    if (current && templates.find(t => t.id === current)) el.value = current;
  });
}

// === ANSWER KEYS ===
async function loadAnswerKeys() {
  try {
    const res = await apiFetch(`${API_URL}/api/answer-keys`);
    if (!res.ok) return;
    answerKeys = await res.json();
    renderAnswerKeyList();
    populateAnswerKeySelects();
  } catch (e) { console.error('Answer keys load failed:', e); }
}

function renderAnswerKeyList() {
  const list = document.getElementById('answerKeyList');
  if (!list) return;
  if (answerKeys.length === 0) {
    list.innerHTML = '<div class="empty-state"><i class="fas fa-key"></i><p>No answer keys</p></div>';
    return;
  }
  list.innerHTML = answerKeys.map(k => `
    <div class="ak-item" onclick="selectAnswerKey('${k.id}')" id="ak-${k.id}">
      <div class="ak-name">${escHtml(k.name)}</div>
      <div class="ak-info">${k.total_questions} questions | ${Object.keys(k.answers || {}).length} answers</div>
      <div class="ak-marks">+${k.correct_marks || 1} / ${k.wrong_marks || 0}</div>
      <div class="ak-actions">
        <button class="btn-sm" style="color:#f44336" onclick="event.stopPropagation();deleteAnswerKey('${k.id}')"><i class="fas fa-trash"></i></button>
      </div>
    </div>
  `).join('');
}

function selectAnswerKey(id) {
  const k = answerKeys.find(x => x.id === id);
  if (!k) return;
  
  document.querySelectorAll('.ak-item').forEach(el => el.classList.remove('active'));
  document.getElementById('ak-' + id)?.classList.add('active');

  const body = document.getElementById('akEditorBody');
  document.getElementById('akEditorTitle').innerHTML = `<i class="fas fa-edit"></i> ${escHtml(k.name)}`;

  // Render answer key detail like Yomark Answer tab
  const answers = k.answers || {};
  let rows = '';
  for (let q = 1; q <= k.total_questions; q++) {
    const ans = answers[q] || answers[String(q)] || 0;
    const ansText = ans > 0 ? String.fromCharCode(64 + ans) : '-';
    rows += `
      <div class="ak-q-row">
        <span class="ak-q-num">Q${q}</span>
        <span class="ak-q-answer">${ansText}</span>
      </div>
    `;
  }
  body.innerHTML = `
    <div style="margin-bottom:12px;">
      <table class="data-grid" style="width:auto">
        <thead><tr>
          <th>Region Name</th><th>Question Text</th><th>Evaluate</th><th>Answer Type</th>
          <th>Marks</th><th>Negative Mark</th><th>Answer</th>
        </tr></thead>
        <tbody>
          ${generateRegionRows(k)}
        </tbody>
      </table>
    </div>
    <h4 style="margin-bottom:8px;color:#1a3a5c;">All Answers</h4>
    <div class="ak-detail-grid">${rows}</div>
  `;
}

function generateRegionRows(k) {
  const totalQ = k.total_questions || 75;
  const regionSize = 15;
  const regions = Math.ceil(totalQ / regionSize);
  let html = '';
  for (let r = 0; r < regions; r++) {
    const start = r * regionSize + 1;
    const end = Math.min(start + regionSize - 1, totalQ);
    const regionName = `${start}-${end}`;
    // Get answers for this region
    const regionAnswers = [];
    for (let q = start; q <= end; q++) {
      const a = k.answers?.[q] || k.answers?.[String(q)] || 0;
      regionAnswers.push(a > 0 ? String.fromCharCode(64 + a) : '-');
    }
    html += `<tr>
      <td style="font-weight:600;background:#e8f5e9">${regionName}</td>
      <td>${regionName}</td>
      <td><input type="checkbox" checked disabled></td>
      <td>SingleCo...</td>
      <td>${k.correct_marks || 1}</td>
      <td>${k.wrong_marks || 0}</td>
      <td style="font-weight:600;color:#1a3a5c">${regionAnswers.join(', ')}</td>
    </tr>`;
    // Individual questions
    for (let q = start; q <= end; q++) {
      const a = k.answers?.[q] || k.answers?.[String(q)] || 0;
      html += `<tr>
        <td></td>
        <td>${q}</td>
        <td><input type="checkbox" checked disabled></td>
        <td>SingleCo...</td>
        <td>${k.correct_marks || 1}</td>
        <td>${k.wrong_marks || 0}</td>
        <td style="font-weight:600">${a > 0 ? String.fromCharCode(64 + a) : '-'}</td>
      </tr>`;
    }
  }
  return html;
}

function showCreateAnswerKey() {
  currentAnswerKeyAnswers = {};
  renderAnswerBubbles();
  openModal('modalCreateAnswerKey');
}

function renderAnswerBubbles() {
  const totalQ = parseInt(document.getElementById('akQuestions').value) || 75;
  const grid = document.getElementById('akBubbleGrid');
  let html = '';
  for (let q = 1; q <= totalQ; q++) {
    html += `<div class="ak-bubble-row">
      <span class="ak-bubble-qnum">Q${q}</span>
      ${[1,2,3,4].map(o => `
        <div class="ak-bubble-opt ${currentAnswerKeyAnswers[q] === o ? 'selected' : ''}" 
             onclick="toggleAnswer(${q}, ${o})">${String.fromCharCode(64+o)}</div>
      `).join('')}
    </div>`;
  }
  grid.innerHTML = html;
}

function toggleAnswer(q, opt) {
  if (currentAnswerKeyAnswers[q] === opt) delete currentAnswerKeyAnswers[q];
  else currentAnswerKeyAnswers[q] = opt;
  renderAnswerBubbles();
}

async function createAnswerKey() {
  const name = document.getElementById('akName').value.trim();
  if (!name) { showToast('Name required', 'error'); return; }
  if (Object.keys(currentAnswerKeyAnswers).length === 0) { showToast('Set at least one answer', 'error'); return; }

  const formData = new FormData();
  formData.append('name', name);
  formData.append('total_questions', document.getElementById('akQuestions').value);
  formData.append('answers_json', JSON.stringify(currentAnswerKeyAnswers));
  formData.append('correct_marks', document.getElementById('akCorrect').value);
  formData.append('wrong_marks', document.getElementById('akWrong').value);
  formData.append('unanswered_marks', document.getElementById('akUnanswered').value);

  try {
    const res = await apiFetch(`${API_URL}/api/answer-keys`, { method: 'POST', body: formData });
    if (!res.ok) throw new Error(await res.text());
    showToast('Answer key created', 'success');
    closeModal('modalCreateAnswerKey');
    loadAnswerKeys();
  } catch (e) { showToast('Failed: ' + e.message, 'error'); }
}

async function deleteAnswerKey(id) {
  if (!confirm('Delete this answer key?')) return;
  try {
    await apiFetch(`${API_URL}/api/answer-keys/${id}`, { method: 'DELETE' });
    showToast('Deleted', 'info');
    loadAnswerKeys();
  } catch { showToast('Failed', 'error'); }
}

function populateAnswerKeySelects() {
  const selects = ['readerAnswerKey', 'sessionAnswerKey'];
  selects.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const current = el.value;
    el.innerHTML = '<option value="">-- No Key --</option>' + 
      answerKeys.map(k => `<option value="${k.id}">${escHtml(k.name)}</option>`).join('');
    if (current) el.value = current;
  });
}

// === SESSIONS ===
async function loadSessions() {
  try {
    const res = await apiFetch(`${API_URL}/api/sessions`);
    if (!res.ok) return;
    sessions = await res.json();
    populateSessionSelects();
  } catch (e) { console.error('Sessions load failed:', e); }
}

function populateSessionSelects() {
  const selects = ['readerSession'];
  selects.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const current = el.value;
    el.innerHTML = '<option value="">-- No Session --</option>' + 
      sessions.filter(s => s.status === 'active').map(s => `<option value="${s.id}">${escHtml(s.name)}</option>`).join('');
    if (current) el.value = current;
  });
}

function showCreateSession() {
  populateTemplateSelects();
  populateAnswerKeySelects();
  openModal('modalCreateSession');
}

async function createSession() {
  const name = document.getElementById('sessionName').value.trim();
  if (!name) { showToast('Session name required', 'error'); return; }

  try {
    const body = {
      name,
      subject: document.getElementById('sessionSubject').value,
      date: document.getElementById('sessionDate').value,
      template_id: document.getElementById('sessionTemplate').value || '75q_4opt',
      answer_key_id: document.getElementById('sessionAnswerKey').value,
      correct_marks: parseFloat(document.getElementById('sessionCorrectMarks').value) || 1,
      wrong_marks: parseFloat(document.getElementById('sessionWrongMarks').value) || 0,
      total_questions: parseInt(document.getElementById('sessionQuestions').value) || 75,
    };
    const res = await apiFetch(`${API_URL}/api/sessions`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
    });
    if (!res.ok) throw new Error(await res.text());
    showToast('Session created', 'success');
    closeModal('modalCreateSession');
    loadSessions();
    loadDashboard();
  } catch (e) { showToast('Failed: ' + e.message, 'error'); }
}

// === STUDENTS ===
async function loadStudents() {
  try {
    const res = await apiFetch(`${API_URL}/api/students`);
    if (!res.ok) return;
    students = await res.json();
    renderStudentsGrid();
  } catch (e) { console.error('Students load failed:', e); }
}

function renderStudentsGrid() {
  const body = document.getElementById('studentsBody');
  if (!body) return;
  if (students.length === 0) {
    body.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:#999">No students yet</td></tr>`;
    return;
  }
  body.innerHTML = students.map((s, i) => `
    <tr>
      <td class="col-num">${i + 1}</td>
      <td style="font-weight:600">${escHtml(s.name)}</td>
      <td>${escHtml(s.enrollment_no)}</td>
      <td>${escHtml(s.class_name || '')}</td>
      <td>${escHtml(s.section || '')}</td>
      <td>${escHtml(s.roll_no || '')}</td>
      <td>${escHtml(s.email || '')}</td>
      <td>${escHtml(s.phone || '')}</td>
      <td><button class="btn-sm" style="color:#f44336" onclick="deleteStudent('${s.id}')"><i class="fas fa-trash"></i></button></td>
    </tr>
  `).join('');
}

function filterStudents() {
  const q = document.getElementById('studentSearch').value.toLowerCase();
  const filtered = students.filter(s => 
    s.name.toLowerCase().includes(q) || 
    s.enrollment_no.toLowerCase().includes(q) ||
    (s.class_name || '').toLowerCase().includes(q)
  );
  const body = document.getElementById('studentsBody');
  body.innerHTML = filtered.map((s, i) => `
    <tr>
      <td class="col-num">${i + 1}</td>
      <td style="font-weight:600">${escHtml(s.name)}</td>
      <td>${escHtml(s.enrollment_no)}</td>
      <td>${escHtml(s.class_name || '')}</td>
      <td>${escHtml(s.section || '')}</td>
      <td>${escHtml(s.roll_no || '')}</td>
      <td>${escHtml(s.email || '')}</td>
      <td>${escHtml(s.phone || '')}</td>
      <td><button class="btn-sm" style="color:#f44336" onclick="deleteStudent('${s.id}')"><i class="fas fa-trash"></i></button></td>
    </tr>
  `).join('');
}

function showAddStudent() { openModal('modalAddStudent'); }

async function addStudent() {
  const name = document.getElementById('stuName').value.trim();
  const enrollment = document.getElementById('stuEnrollment').value.trim();
  if (!name || !enrollment) { showToast('Name and enrollment required', 'error'); return; }

  try {
    const body = {
      name,
      enrollment_no: enrollment,
      class_name: document.getElementById('stuClass').value,
      section: document.getElementById('stuSection').value,
      roll_no: document.getElementById('stuRollNo').value,
      email: document.getElementById('stuEmail').value,
      phone: document.getElementById('stuPhone').value,
    };
    const res = await apiFetch(`${API_URL}/api/students`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
    });
    if (!res.ok) throw new Error(await res.text());
    showToast('Student added', 'success');
    closeModal('modalAddStudent');
    // Clear form
    ['stuName','stuEnrollment','stuClass','stuSection','stuRollNo','stuEmail','stuPhone'].forEach(id => document.getElementById(id).value = '');
    loadStudents();
  } catch (e) { showToast('Failed: ' + e.message, 'error'); }
}

async function deleteStudent(id) {
  if (!confirm('Delete this student?')) return;
  try {
    await apiFetch(`${API_URL}/api/students/${id}`, { method: 'DELETE' });
    showToast('Student removed', 'info');
    loadStudents();
  } catch { showToast('Failed', 'error'); }
}

function showBulkImport() {
  showToast('Bulk import: Prepare CSV with columns: name, enrollment_no, class_name, section, roll_no, email, phone', 'info');
}

// === READER / SCANNING ===
function handleFileSelect(event) {
  selectedFiles = Array.from(event.target.files);
  document.getElementById('fileCount').textContent = selectedFiles.length + ' files selected';
  showToast(`${selectedFiles.length} files loaded. Click "Start Reading" to process.`, 'info');
}

function handleFolderSelect(event) {
  const files = Array.from(event.target.files).filter(f => f.type.startsWith('image/'));
  selectedFiles = files;
  document.getElementById('fileCount').textContent = files.length + ' image files from folder';
  showToast(`${files.length} images found in folder. Click "Start Reading" to process.`, 'info');
}

function onReaderTemplateChange() {
  const tplId = document.getElementById('readerTemplate').value;
  const tpl = templates.find(t => t.id === tplId);
  if (tpl) {
    // Update response grid headers with question numbers
    updateResponseGridHeaders(tpl.total_questions);
  }
}

function updateResponseGridHeaders(totalQ) {
  const thead = document.querySelector('#responseGrid thead tr');
  // Keep first 7 columns, add question columns
  const baseCols = `
    <th class="col-num">#</th>
    <th class="col-file">File Name</th>
    <th class="col-name">Student Name</th>
    <th class="col-roll">Roll Number</th>
    <th class="col-class">Class</th>
    <th class="col-medium">Medium</th>
    <th class="col-mobile">Mobile Number</th>
  `;
  let qCols = '';
  for (let q = 1; q <= Math.min(totalQ, 75); q++) {
    qCols += `<th class="col-ans">${q}</th>`;
  }
  thead.innerHTML = baseCols + qCols;
}

async function startProcessing() {
  if (selectedFiles.length === 0) {
    showToast('No files selected. Use Browse to select OMR images.', 'error');
    return;
  }

  processingAborted = false;
  const templateId = document.getElementById('readerTemplate').value || '75q_4opt';
  const sessionId = document.getElementById('readerSession').value;
  const answerKeyId = document.getElementById('readerAnswerKey').value;
  const progressBar = document.getElementById('progressBar');
  const progressFill = document.getElementById('progressFill');
  const statusEl = document.getElementById('readerStatus');

  // Determine session name for image saving
  let sessionName = 'default';
  if (sessionId) {
    const sess = sessions.find(s => s.id === sessionId);
    if (sess) sessionName = sess.name;
  } else {
    sessionName = 'Batch_' + new Date().toISOString().slice(0,10);
  }
  currentProcessingSession = sessionName;

  progressBar.style.display = 'block';
  const totalFiles = selectedFiles.length;
  let successCount = 0;
  let errorCount = 0;
  const startTime = Date.now();

  statusEl.textContent = `Processing ${totalFiles} sheets one by one...`;

  // Process files one by one for reliability with 500+ images
  for (let i = 0; i < totalFiles; i++) {
    if (processingAborted) {
      statusEl.textContent = `Stopped: ${successCount}/${totalFiles} processed (${errorCount} errors)`;
      showToast(`Processing stopped at ${i}/${totalFiles}`, 'info');
      break;
    }

    const file = selectedFiles[i];
    const pct = Math.round(((i + 1) / totalFiles) * 100);
    progressFill.style.width = pct + '%';

    const elapsed = ((Date.now() - startTime) / 1000).toFixed(0);
    const perSheet = i > 0 ? ((Date.now() - startTime) / i / 1000).toFixed(1) : '?';
    const remaining = i > 0 ? Math.round(((totalFiles - i) * (Date.now() - startTime) / i) / 1000) : '?';
    statusEl.textContent = `Processing ${i + 1}/${totalFiles} (${pct}%) | ${elapsed}s elapsed | ~${remaining}s remaining | ${perSheet}s/sheet`;

    try {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('template_id', templateId);
      formData.append('session_name', sessionName);
      formData.append('sheet_index', i);
      if (answerKeyId) formData.append('answer_key_id', answerKeyId);
      if (sessionId) formData.append('session_id', sessionId);

      const res = await apiFetch(`${API_URL}/api/process-single-save`, { method: 'POST', body: formData });
      if (!res.ok) throw new Error(await res.text());
      const data = await res.json();

      successCount++;
      allResults.push(data);
      addResultToResponseGrid(data, allResults.length);
      addResultToResultsGrid(data);

      // Show preview for latest processed image
      if (i === totalFiles - 1 || i % 10 === 0) {
        updateImagePreview(data);
      }

      document.getElementById('gridInfo').textContent = `${allResults.length} rows`;
    } catch (e) {
      errorCount++;
      console.error(`Error processing ${file.name}:`, e);
      // Continue processing next file - don't stop for one error
    }
  }

  progressFill.style.width = '100%';
  const totalTime = ((Date.now() - startTime) / 1000).toFixed(1);
  statusEl.textContent = `Done: ${successCount}/${totalFiles} processed | ${errorCount} errors | ${totalTime}s total`;
  
  if (successCount > 0) {
    showToast(`Batch complete: ${successCount}/${totalFiles} sheets processed in ${totalTime}s. Images saved with enrollment names.`, 'success');
    if (allResults.length > 0) updateImagePreview(allResults[allResults.length - 1]);
    // Show download button
    showDownloadButton(sessionName);
  } else {
    showToast(`All ${totalFiles} sheets failed to process`, 'error');
  }

  setTimeout(() => { progressBar.style.display = 'none'; }, 3000);
  loadDashboard();
  if (sessionId) loadSessions();
}

let currentProcessingSession = 'default';

function stopProcessing() {
  processingAborted = true;
  document.getElementById('readerStatus').textContent = 'Processing stopped by user';
  showToast('Processing stopped', 'info');
}

function showDownloadButton(sessionName) {
  const statusEl = document.getElementById('readerStatus');
  const currentText = statusEl.textContent;
  statusEl.innerHTML = currentText + 
    ` <button class="btn-sm btn-primary" onclick="downloadProcessedImages('${escHtml(sessionName)}')" style="margin-left:12px;padding:4px 12px;font-size:12px;">` +
    `<i class="fas fa-download"></i> Download Images (${sessionName})</button>`;
}

async function downloadProcessedImages(sessionName) {
  showToast('Preparing ZIP download...', 'info');
  try {
    const res = await apiFetch(`${API_URL}/api/download-processed/${encodeURIComponent(sessionName)}`);
    if (!res.ok) throw new Error('No processed images found');
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${sessionName}_processed_images.zip`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    showToast('Download started!', 'success');
  } catch (e) {
    showToast('Download failed: ' + e.message, 'error');
  }
}

function addResultToResponseGrid(r, index) {
  const tbody = document.getElementById('responseBody');
  const answers = r.answers || {};
  const tplId = document.getElementById('readerTemplate').value || '75q_4opt';
  const tpl = templates.find(t => t.id === tplId);
  const totalQ = tpl ? tpl.total_questions : 75;

  let ansCols = '';
  for (let q = 1; q <= Math.min(totalQ, 75); q++) {
    const a = answers[q] || answers[String(q)] || 0;
    let txt = '-';
    if (a > 0) txt = String.fromCharCode(64 + a);
    else if (a === -1) txt = 'M';
    ansCols += `<td class="col-ans">${txt}</td>`;
  }

  // Color coding like Yomark
  let rowClass = '';
  if (r.grading) {
    if (r.grading.percentage >= 60) rowClass = 'highlight-green';
    else if (r.grading.percentage >= 40) rowClass = 'highlight-yellow';
    else rowClass = 'highlight-pink';
  }

  const tr = document.createElement('tr');
  tr.className = rowClass;
  tr.style.cursor = 'pointer';
  tr.onclick = () => {
    document.querySelectorAll('#responseBody tr').forEach(t => t.classList.remove('selected'));
    tr.classList.add('selected');
    updateImagePreview(r);
  };
  tr.innerHTML = `
    <td class="col-num">${index}</td>
    <td class="col-file" title="${escHtml(r.filename)}">${escHtml(r.filename)}</td>
    <td class="col-name">${escHtml(r.student_name || '')}</td>
    <td class="col-roll">${escHtml(r.enrollment_no || '')}</td>
    <td class="col-class"></td>
    <td class="col-medium"></td>
    <td class="col-mobile"></td>
    ${ansCols}
  `;
  tbody.appendChild(tr);
  document.getElementById('gridInfo').textContent = `${tbody.children.length} rows`;
}

function addResultToResultsGrid(r) {
  if (!r.grading) return;
  const tbody = document.getElementById('resultsBody');
  const g = r.grading;
  let rowClass = g.percentage >= 60 ? 'highlight-green' : g.percentage >= 40 ? 'highlight-yellow' : 'highlight-pink';
  
  const tr = document.createElement('tr');
  tr.className = rowClass;
  tr.innerHTML = `
    <td class="col-num">${tbody.children.length + 1}</td>
    <td>${escHtml(r.filename)}</td>
    <td style="font-weight:600">${escHtml(r.student_name || r.enrollment_no || '')}</td>
    <td>${escHtml(r.enrollment_no || '')}</td>
    <td class="col-score" style="font-weight:700">${g.score}/${g.max_score}</td>
    <td class="col-pct"><span style="padding:2px 8px;border-radius:8px;font-weight:600;font-size:11px;background:${g.percentage>=60?'#e8f5e9':g.percentage>=40?'#fff8e1':'#fce4ec'};color:${g.percentage>=60?'#2E7D32':g.percentage>=40?'#F57F17':'#C62828'}">${g.percentage}%</span></td>
    <td class="col-correct" style="color:#4CAF50;font-weight:600">${g.correct}</td>
    <td class="col-wrong" style="color:#f44336;font-weight:600">${g.wrong}</td>
    <td class="col-unanswered">${g.unanswered}</td>
    <td class="col-rank">-</td>
  `;
  tbody.appendChild(tr);
}

function updateImagePreview(r) {
  const preview = document.getElementById('imagePreview');
  if (r.debug_image) {
    preview.innerHTML = `<img src="data:image/jpeg;base64,${r.debug_image}" alt="Sheet preview">`;
  } else {
    preview.innerHTML = '<div class="empty-state"><i class="fas fa-image"></i><p>No preview available</p></div>';
  }
}

function toggleImagePreview() {
  const panel = document.querySelector('.reader-image-panel');
  panel.style.display = document.getElementById('showImageToggle').checked ? 'flex' : 'none';
}

function clearResults() {
  allResults = [];
  document.getElementById('responseBody').innerHTML = '';
  document.getElementById('resultsBody').innerHTML = '';
  document.getElementById('gridInfo').textContent = '0 rows';
  document.getElementById('imagePreview').innerHTML = '<div class="empty-state"><i class="fas fa-image"></i><p>Select a row to preview</p></div>';
  showToast('Results cleared', 'info');
}

function findStudent() {
  const q = prompt('Search by name or enrollment:');
  if (!q) return;
  const rows = document.querySelectorAll('#responseBody tr');
  rows.forEach(r => {
    const text = r.textContent.toLowerCase();
    r.style.display = text.includes(q.toLowerCase()) ? '' : 'none';
  });
}

function saveImage() { showToast('Right-click the image and select "Save As" to save', 'info'); }
function reloadInfo() { loadAll(); showToast('Data reloaded', 'info'); }

function updateThresholdDisplay() {
  document.getElementById('thresholdVal').textContent = document.getElementById('bubbleThreshold').value + '%';
}

// === ALL RESULTS TAB ===
function renderAllResultsGrid() {
  const body = document.getElementById('allResultsBody');
  body.innerHTML = allResults.map((r, i) => {
    const g = r.grading;
    return `<tr>
      <td class="col-num">${i + 1}</td>
      <td>${escHtml(r.filename)}</td>
      <td>${escHtml(r.enrollment_no || '-')}</td>
      <td style="font-weight:600">${escHtml(r.student_name || '-')}</td>
      <td style="text-align:center">${r.answered_count}/${r.total_questions}</td>
      <td style="text-align:center">${r.unanswered_count}</td>
      <td style="text-align:center">${r.multiple_marked}</td>
      <td style="text-align:center;font-weight:700">${g ? g.score : '-'}</td>
      <td style="text-align:center">${g ? g.max_score : '-'}</td>
      <td style="text-align:center">${g ? `<span style="padding:2px 8px;border-radius:8px;font-weight:600;font-size:11px;background:${g.percentage>=60?'#e8f5e9':g.percentage>=40?'#fff8e1':'#fce4ec'}">${g.percentage}%</span>` : '-'}</td>
      <td style="text-align:center;color:#4CAF50">${g ? g.correct : '-'}</td>
      <td style="text-align:center;color:#f44336">${g ? g.wrong : '-'}</td>
      <td style="text-align:center">${((r.confidence || 0) * 100).toFixed(1)}%</td>
      <td style="text-align:center"><button class="btn-sm" onclick="showResultDetail(${i})"><i class="fas fa-eye"></i></button></td>
    </tr>`;
  }).join('');
}

function showResultDetail(index) {
  const r = allResults[index];
  if (!r) return;
  const g = r.grading;
  
  let detailHtml = `<div class="result-detail">
    <div class="result-detail-info">
      <table class="result-info-table">
        <tr><td>File:</td><td>${escHtml(r.filename)}</td></tr>
        <tr><td>Enrollment:</td><td>${escHtml(r.enrollment_no || 'Not detected')}</td></tr>
        <tr><td>Student:</td><td>${escHtml(r.student_name || 'Unknown')}</td></tr>
        <tr><td>Answered:</td><td style="color:#4CAF50;font-weight:600">${r.answered_count}</td></tr>
        <tr><td>Unanswered:</td><td>${r.unanswered_count}</td></tr>
        <tr><td>Multi-Marked:</td><td style="color:#f44336">${r.multiple_marked}</td></tr>
        <tr><td>Confidence:</td><td>${((r.confidence||0)*100).toFixed(1)}%</td></tr>
      </table>`;

  if (g) {
    detailHtml += `
      <div class="grading-cards">
        <div class="grade-card grade-correct"><div class="grade-value">${g.correct}</div><div class="grade-label">Correct</div></div>
        <div class="grade-card grade-wrong"><div class="grade-value">${g.wrong}</div><div class="grade-label">Wrong</div></div>
        <div class="grade-card grade-score"><div class="grade-value">${g.score}/${g.max_score}</div><div class="grade-label">Score</div></div>
        <div class="grade-card grade-pct"><div class="grade-value">${g.percentage}%</div><div class="grade-label">Percentage</div></div>
      </div>
      <h4 style="margin-bottom:8px">Question-wise Analysis</h4>
      <div class="question-grid">
        ${g.details ? g.details.map(d => {
          const cls = d.status === 'correct' ? 'q-correct' : d.status === 'wrong' ? 'q-wrong' : d.status === 'multiple_marked' ? 'q-multi' : 'q-unanswered';
          return `<div class="q-cell ${cls}">
            <div class="q-num">Q${d.question}</div>
            <div>Ans: ${d.student_answer > 0 ? String.fromCharCode(64+d.student_answer) : d.student_answer === -1 ? 'M' : '-'}</div>
            <div>Key: ${d.correct_answer > 0 ? String.fromCharCode(64+d.correct_answer) : '-'}</div>
          </div>`;
        }).join('') : ''}
      </div>`;
  }

  detailHtml += `</div><div class="result-detail-image">`;
  if (r.debug_image) {
    detailHtml += `<img src="data:image/jpeg;base64,${r.debug_image}" alt="Debug">`;
  } else {
    detailHtml += '<div class="empty-state"><p>No debug image</p></div>';
  }
  detailHtml += '</div></div>';

  document.getElementById('resultDetailBody').innerHTML = detailHtml;
  openModal('modalResultDetail');
}

// === EXPORT ===
async function exportResults(format) {
  const formData = new FormData();
  if (allResults.length > 0) {
    formData.append('result_ids', allResults.map(r => r.result_id).join(','));
  } else {
    showToast('No results to export', 'error');
    return;
  }
  try {
    const endpoint = format === 'csv' ? '/api/export/csv' : '/api/export/excel';
    const res = await apiFetch(`${API_URL}${endpoint}`, { method: 'POST', body: formData });
    if (!res.ok) throw new Error('Export failed');
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `omr_results.${format === 'csv' ? 'csv' : 'xlsx'}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    showToast(`Exported as ${format.toUpperCase()}`, 'success');
  } catch (e) { showToast('Export failed', 'error'); }
}

// === REPORTS / ANALYTICS ===
function populateReportSessionSelect() {
  const el = document.getElementById('reportSession');
  if (!el) return;
  el.innerHTML = '<option value="">-- Select Session --</option>' +
    sessions.map(s => `<option value="${s.id}">${escHtml(s.name)} (${s.total_scanned} scanned)</option>`).join('');
}

async function loadSessionAnalytics() {
  const sessionId = document.getElementById('reportSession').value;
  if (!sessionId) { showToast('Select a session first', 'error'); return; }

  try {
    const res = await apiFetch(`${API_URL}/api/analytics/session/${sessionId}`);
    if (!res.ok) throw new Error('Analytics failed');
    const data = await res.json();
    
    document.getElementById('reportEmpty').style.display = 'none';
    document.getElementById('reportContent').style.display = 'block';

    // Stats
    document.getElementById('rptScanned').textContent = data.total_scanned;
    document.getElementById('rptAvgScore').textContent = data.average_percentage + '%';
    document.getElementById('rptHighest').textContent = data.highest_score;
    document.getElementById('rptLowest').textContent = data.lowest_score;
    document.getElementById('rptPassed').textContent = data.passed;
    document.getElementById('rptFailed').textContent = data.failed;

    // Toppers
    const toppersList = document.getElementById('toppersList');
    if (data.toppers && data.toppers.length > 0) {
      toppersList.innerHTML = data.toppers.map(t => `
        <div class="topper-item">
          <div class="topper-rank ${t.rank <= 3 ? 'rank-' + t.rank : 'rank-other'}">${t.rank}</div>
          <div class="topper-info">
            <div class="topper-name">${escHtml(t.student_name || t.enrollment_no || t.filename)}</div>
            <div class="topper-details">Correct: ${t.correct} | Wrong: ${t.wrong}</div>
          </div>
          <div class="topper-score">
            <div class="topper-score-value">${t.score}</div>
            <div class="topper-pct">${t.percentage}%</div>
          </div>
        </div>
      `).join('');
    } else {
      toppersList.innerHTML = '<div class="empty-state"><p>No graded results</p></div>';
    }

    // Score Distribution
    const distChart = document.getElementById('reportDistChart');
    const dist = data.score_distribution || {};
    const maxVal = Math.max(...Object.values(dist), 1);
    const distColors = { '0-20': '#f44336', '21-40': '#FF9800', '41-60': '#FFC107', '61-80': '#2196F3', '81-100': '#4CAF50' };
    distChart.innerHTML = Object.entries(dist).map(([range, count]) => `
      <div class="dist-row">
        <span class="dist-label">${range}%</span>
        <div class="dist-bar-bg">
          <div class="dist-bar" style="width:${Math.max((count/maxVal)*100, count>0?10:0)}%;background:${distColors[range]||'#2196F3'}">
            ${count > 0 ? count : ''}
          </div>
        </div>
      </div>
    `).join('');

    document.getElementById('pfPassed').textContent = data.passed;
    document.getElementById('pfFailed').textContent = data.failed;

    // Question Analysis
    const qaBody = document.getElementById('questionAnalysisBody');
    if (data.question_analysis && data.question_analysis.length > 0) {
      qaBody.innerHTML = data.question_analysis.map(q => {
        const level = q.difficulty_index >= 70 ? 'hard' : q.difficulty_index >= 40 ? 'medium' : 'easy';
        return `<tr>
          <td style="font-weight:600">Q${q.question}</td>
          <td style="text-align:center;color:#4CAF50">${q.correct}</td>
          <td style="text-align:center;color:#f44336">${q.wrong}</td>
          <td style="text-align:center">${q.unanswered}</td>
          <td style="text-align:center">${q.difficulty_index}%</td>
          <td><span class="difficulty-${level}">${level.charAt(0).toUpperCase() + level.slice(1)}</span></td>
        </tr>`;
      }).join('');
    } else {
      qaBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#999">No question analysis available</td></tr>';
    }

    // Reporting Format table (like Yomark)
    renderReportingTable(data);
    
    showToast('Analytics loaded', 'success');
  } catch (e) {
    showToast('Failed to load analytics: ' + e.message, 'error');
  }
}

function renderReportingTable(data) {
  const body = document.getElementById('reportingBody');
  if (!data.toppers || data.toppers.length === 0) {
    body.innerHTML = '<tr><td colspan="14" style="text-align:center;padding:20px;color:#999">No data</td></tr>';
    return;
  }

  body.innerHTML = data.toppers.map((t, i) => {
    // Calculate section scores (1-15, 16-30, etc.)
    const s1 = '-', s2 = '-', s3 = '-', s4 = '-', s5 = '-';
    const pctClass = t.percentage >= 60 ? 'highlight-green' : t.percentage >= 40 ? 'highlight-yellow' : 'highlight-pink';
    return `<tr class="${pctClass}">
      <td class="col-num">${i + 1}</td>
      <td style="font-weight:600">${escHtml(t.student_name || t.enrollment_no || t.filename)}</td>
      <td>${escHtml(t.enrollment_no || '')}</td>
      <td></td>
      <td style="text-align:center">${s1}</td>
      <td style="text-align:center">${s2}</td>
      <td style="text-align:center">${s3}</td>
      <td style="text-align:center">${s4}</td>
      <td style="text-align:center">${s5}</td>
      <td style="text-align:center;font-weight:700">${t.score}</td>
      <td style="text-align:center;font-weight:700">${t.rank}</td>
      <td style="text-align:center"><span style="padding:2px 8px;border-radius:8px;font-weight:600;font-size:11px;background:${t.percentage>=60?'#e8f5e9':t.percentage>=40?'#fff8e1':'#fce4ec'}">${t.percentage}%</span></td>
      <td style="text-align:center;color:#4CAF50">${t.correct}</td>
      <td style="text-align:center;color:#f44336">${t.wrong}</td>
      <td style="text-align:center">${(data.total_scanned || 75) - (t.correct + t.wrong)}</td>
    </tr>`;
  }).join('');
}

// === SETTINGS ===
async function saveSettings() {
  try {
    const threshold = document.getElementById('settingThreshold').value;
    const formData = new FormData();
    formData.append('fill_threshold', threshold);
    const res = await apiFetch(`${API_URL}/api/settings/threshold`, { method: 'POST', body: formData });
    if (res.ok) showToast('Settings saved', 'success');
    else showToast('Save failed', 'error');
  } catch { showToast('Save failed', 'error'); }
}

function updateApiUrl() {
  const url = document.getElementById('settingApiUrl').value.trim();
  if (url) {
    API_URL = url;
    localStorage.setItem('omr_api_url', url);
    checkConnection();
    loadAll();
    showToast('API URL updated to: ' + url, 'success');
  }
}

// === LAYOUT TOOLBAR ACTIONS ===
function newTemplate() { showCreateTemplate(); }
function addPivot() { showToast('Pivot markers help with perspective correction during scanning', 'info'); }
function addOMRRegion() { showToast('OMR regions define the bubble areas on the sheet template', 'info'); }
function addBarcode() { showToast('Barcode region detects enrollment/roll numbers automatically', 'info'); }

// === UTILITY ===
function escHtml(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// Initialize with first template selected
setTimeout(() => {
  if (templates.length > 0) {
    selectTemplate(templates[0].id);
    onReaderTemplateChange();
  }
}, 500);
