// app.js — Vanilla JS client for the TODO app
// Talks to /api/todos.php (GET, POST, PATCH, DELETE)

const API = '/api/todos.php';

async function fetchTodos() {
  const res = await fetch(API);
  if (!res.ok) throw new Error('Failed to fetch todos');
  return res.json();
}

function renderTodos(todos) {
  var list = document.getElementById('todo-list');
  var emptyMsg = document.getElementById('empty-msg');
  list.innerHTML = '';

  emptyMsg.style.display = todos.length === 0 ? '' : 'none';

  todos.forEach(function (todo) {
    var li = document.createElement('li');
    li.className = 'todo-item' + (todo.done ? ' done' : '');

    var checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = !!todo.done;
    checkbox.addEventListener('change', function () {
      toggleTodo(todo.id);
    });

    var span = document.createElement('span');
    span.className = 'todo-text';
    span.textContent = todo.text;

    var deleteBtn = document.createElement('button');
    deleteBtn.className = 'delete-btn';
    deleteBtn.textContent = '\u00D7';
    deleteBtn.addEventListener('click', function () {
      deleteTodo(todo.id);
    });

    li.appendChild(checkbox);
    li.appendChild(span);
    li.appendChild(deleteBtn);
    list.appendChild(li);
  });
}

async function loadAndRender() {
  const todos = await fetchTodos();
  renderTodos(todos);
}

async function addTodo(text) {
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text: text }),
  });
  if (!res.ok) throw new Error('Failed to add todo');
}

async function toggleTodo(id) {
  const res = await fetch(API + '?id=' + encodeURIComponent(id), {
    method: 'PATCH',
  });
  if (!res.ok) throw new Error('Failed to toggle todo');
  await loadAndRender();
}

async function deleteTodo(id) {
  const res = await fetch(API + '?id=' + encodeURIComponent(id), {
    method: 'DELETE',
  });
  if (!res.ok) throw new Error('Failed to delete todo');
  await loadAndRender();
}

document.addEventListener('DOMContentLoaded', function () {
  loadAndRender();

  const form = document.getElementById('todo-form');
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const input = document.getElementById('todo-input');
    const text = input.value.trim();
    if (!text) return; // Do not POST empty text
    await addTodo(text);
    input.value = '';
    await loadAndRender();
  });
});
