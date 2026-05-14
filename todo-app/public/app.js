// TODO app - JS vanilla, sense frameworks
const API = '/api/todos.php';
const listEl = document.getElementById('todos');
const formEl = document.getElementById('add-form');
const inputEl = document.getElementById('add-input');
const errorEl = document.getElementById('error');

function showError(msg) {
  errorEl.textContent = msg || '';
}

async function api(method, url, body) {
  try {
    const opts = { method, headers: {} };
    if (body !== undefined) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`HTTP ${res.status}: ${text}`);
    }
    // DELETE pot no tenir cos
    const ct = res.headers.get('Content-Type') || '';
    return ct.includes('application/json') ? await res.json() : null;
  } catch (err) {
    showError('Error API: ' + err.message);
    throw err;
  }
}

function render(todos) {
  // Buidem amb DOM, sense innerHTML
  while (listEl.firstChild) listEl.removeChild(listEl.firstChild);

  for (const t of todos) {
    const li = document.createElement('li');
    if (t.done) li.classList.add('done');

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = !!t.done;
    checkbox.addEventListener('change', () => toggle(t));

    const span = document.createElement('span');
    span.className = 'text';
    span.textContent = t.text; // textContent contra XSS

    const delBtn = document.createElement('button');
    delBtn.className = 'delete';
    delBtn.type = 'button';
    delBtn.textContent = 'Eliminar';
    delBtn.addEventListener('click', () => remove(t));

    li.appendChild(checkbox);
    li.appendChild(span);
    li.appendChild(delBtn);
    listEl.appendChild(li);
  }
}

async function load() {
  showError('');
  const todos = await api('GET', API);
  render(todos || []);
}

async function add(text) {
  showError('');
  await api('POST', API, { text });
  await load();
}

async function toggle(t) {
  showError('');
  await api('PATCH', `${API}?id=${encodeURIComponent(t.id)}`, { done: !t.done });
  await load();
}

async function remove(t) {
  showError('');
  await api('DELETE', `${API}?id=${encodeURIComponent(t.id)}`);
  await load();
}

formEl.addEventListener('submit', (e) => {
  e.preventDefault();
  const text = inputEl.value.trim();
  if (!text) return;
  inputEl.value = '';
  add(text).catch(() => {});
});

document.addEventListener('DOMContentLoaded', () => {
  load().catch(() => {});
});
