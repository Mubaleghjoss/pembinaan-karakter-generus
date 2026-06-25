<?php $header = file_get_contents(__DIR__.'/../header.php');
echo $header; ?>
<div class="min-h-screen bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Kelola Berita</h1>
        <p class="text-sm text-gray-500">Tambah, ubah, hapus, dan kelola status berita.</p>
      </div>
      <div class="space-x-2">
        <button id="btn-refresh" class="inline-flex items-center px-3 py-2 rounded-md bg-white text-gray-700 border hover:bg-gray-50 shadow-sm">Refresh</button>
        <button id="btn-add" class="inline-flex items-center px-3 py-2 rounded-md bg-sky-600 text-white hover:bg-sky-700 shadow">+ Tambah Berita</button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
        <input id="q" type="text" placeholder="Cari judul, tag..." class="w-full px-3 py-2 border rounded-md focus:ring-sky-500 focus:border-sky-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select id="status" class="w-full px-3 py-2 border rounded-md focus:ring-sky-500 focus:border-sky-500">
          <option value="">Semua</option>
          <option value="published">published</option>
          <option value="draft">draft</option>
        </select>
      </div>
    </div>

    <div id="grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach (($berita ?? []) as $b) { ?>
      <div class="bg-white rounded-lg shadow p-4 flex flex-col">
        <div class="flex-1">
          <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1"><?= htmlspecialchars($b['judul'] ?? '') ?></h3>
          <p class="text-xs text-gray-500 mb-2">#<?= htmlspecialchars($b['slug'] ?? '') ?></p>
          <div class="text-sm text-gray-700 line-clamp-3 mb-3"><?= htmlspecialchars(mb_substr($b['isi'] ?? '', 0, 120)) ?></div>
        </div>
        <div class="mt-3 flex items-center justify-between">
          <span class="text-xs px-2 py-0.5 rounded <?= (($b['status'] ?? 'draft') === 'published') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>"><?= htmlspecialchars($b['status'] ?? 'draft') ?></span>
          <div class="space-x-2">
            <button class="btn-edit text-sky-700 hover:underline" data-id="<?= (int) $b['id'] ?>">Edit</button>
            <button class="btn-delete text-red-600 hover:underline" data-id="<?= (int) $b['id'] ?>">Hapus</button>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>
</div>

<!-- Modal Form -->
<div id="modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-xl">
    <div class="px-4 py-3 border-b flex items-center justify-between">
      <h3 id="modal-title" class="font-semibold">Tambah Berita</h3>
      <button id="modal-close" class="text-gray-500 hover:text-gray-700">✕</button>
    </div>
    <form id="form" class="p-4 space-y-3">
      <input type="hidden" id="id">
      <div>
        <label class="block text-sm font-medium text-gray-700">Judul</label>
        <input id="judul" type="text" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-sky-500 focus:border-sky-500" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Isi</label>
        <textarea id="isi" rows="5" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-sky-500 focus:border-sky-500"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Tags (comma)</label>
          <input id="tags" type="text" class="mt-1 w-full px-3 py-2 border rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select id="status_input" class="mt-1 w-full px-3 py-2 border rounded-md">
            <option value="draft">draft</option>
            <option value="published">published</option>
          </select>
        </div>
      </div>
      <div class="pt-2 flex justify-end space-x-2">
        <button type="button" id="btn-cancel" class="px-3 py-2 rounded-md border text-gray-700 hover:bg-gray-50">Batal</button>
        <button type="submit" class="px-3 py-2 rounded-md bg-sky-600 text-white hover:bg-sky-700">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  // Base detection: respect global APP_BASE set by header.php
  if (typeof window.APP_BASE === 'undefined' || !window.APP_BASE) {
    window.APP_BASE = (function(){
      var p = window.location.pathname || '/';
      if (p.startsWith('/pkg/public')) return '/pkg/public';
      if (p.startsWith('/public')) return '/public';
      return '';
    })();
  }

  const grid = document.getElementById('grid');
  const q = document.getElementById('q');
  const status = document.getElementById('status');
  const btnAdd = document.getElementById('btn-add');
  const btnRefresh = document.getElementById('btn-refresh');

  const modal = document.getElementById('modal');
  const modalTitle = document.getElementById('modal-title');
  const close = document.getElementById('modal-close');
  const cancel = document.getElementById('btn-cancel');
  const f = (id)=>document.getElementById(id);

  function openModal(title){ modalTitle.textContent=title; modal.classList.remove('hidden'); modal.classList.add('flex'); }
  function closeModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); f('id').value=''; f('judul').value=''; f('isi').value=''; f('tags').value=''; f('status_input').value='draft'; }

  function cardHtml(b){
    return `<div class="bg-white rounded-lg shadow p-4 flex flex-col">
      <div class="flex-1">
        <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1">${(b.judul||'')}</h3>
        <p class="text-xs text-gray-500 mb-2">#${(b.slug||'')}</p>
        <div class="text-sm text-gray-700 line-clamp-3 mb-3">${(b.isi||'').slice(0,120)}</div>
      </div>
      <div class="mt-3 flex items-center justify-between">
        <span class="text-xs px-2 py-0.5 rounded ${ (b.status||'draft')==='published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }">${b.status||'draft'}</span>
        <div class="space-x-2">
          <button class="text-sky-700 hover:underline" data-action="edit" data-id="${b.id}">Edit</button>
          <button class="text-red-600 hover:underline" data-action="delete" data-id="${b.id}">Hapus</button>
        </div>
      </div>
    </div>`;
  }

  function render(list){
    grid.innerHTML = '';
    (list||[]).forEach(b=>{
      const div = document.createElement('div');
      div.innerHTML = cardHtml(b);
      const node = div.firstElementChild;
      grid.appendChild(node);
    });
  }

  async function loadData(){
    const r = await fetch(window.APP_BASE + '/api/berita');
    const data = await r.json();
    window.__ALL_BERITA__ = Array.isArray(data) ? data : [];
    applyFilters();
  }

  function applyFilters(){
    const keyword = (q.value||'').toLowerCase();
    const st = status.value||'';
    const filtered = (window.__ALL_BERITA__||[]).filter(b=>{
      const matchStatus = !st || String(b.status||'')===String(st);
      const matchKeyword = !keyword || [b.judul, b.tags, b.slug].filter(Boolean).some(v=>String(v).toLowerCase().includes(keyword));
      return matchStatus && matchKeyword;
    });
    render(filtered);
  }

  async function createItem(payload){
    const r = await fetch(window.APP_BASE + '/api/berita', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    if(!r.ok) throw new Error('Gagal menambah berita');
  }
  async function updateItem(id, payload){
    const r = await fetch(window.APP_BASE + '/api/berita/'+id, { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    if(!r.ok) throw new Error('Gagal mengubah berita');
  }
  async function deleteItem(id){
    const r = await fetch(window.APP_BASE + '/api/berita/'+id, { method:'DELETE' });
    if(!r.ok) throw new Error('Gagal menghapus berita');
  }

  // events
  btnRefresh.addEventListener('click', loadData);
  q.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); applyFilters(); }});
  status.addEventListener('change', applyFilters);
  btnAdd.addEventListener('click', ()=>{ openModal('Tambah Berita'); });
  close.addEventListener('click', closeModal); cancel.addEventListener('click', closeModal);

  grid.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button'); if(!btn) return;
    const action = btn.dataset.action; const id = btn.dataset.id;
    if(action==='edit'){
      const item = (window.__ALL_BERITA__||[]).find(x=>String(x.id)===String(id));
      if(!item) return;
      f('id').value=item.id; f('judul').value=item.judul||''; f('isi').value=item.isi||''; f('tags').value=item.tags||''; f('status_input').value=item.status||'draft';
      openModal('Ubah Berita');
    } else if(action==='delete'){
      if(confirm('Yakin hapus berita ini?')){
        try{ await deleteItem(id); await loadData(); alert('Berhasil dihapus'); }catch(err){ alert(err.message||'Gagal menghapus'); }
      }
    }
  });

  document.getElementById('form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const payload = {
      judul: f('judul').value||'',
      isi: f('isi').value||'',
      tags: f('tags').value||null,
      status: f('status_input').value||'draft',
    };
    try{
      const id = f('id').value;
      if(id){ await updateItem(id, payload); } else { await createItem(payload); }
      closeModal(); await loadData(); alert('Tersimpan');
    }catch(err){ alert(err.message||'Gagal menyimpan'); }
  });

  // fix base for internal links
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('a[href^="/"]').forEach(a=>{ const href=a.getAttribute('href'); if(!href.startsWith(window.APP_BASE)) a.setAttribute('href', window.APP_BASE + href); });
  });

  loadData();
})();
</script>