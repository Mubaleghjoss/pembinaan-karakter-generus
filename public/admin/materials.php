<?php $header = file_get_contents(__DIR__.'/../header.php');
echo $header; ?>
<div class="min-h-screen bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Kelola Materi</h1>
        <p class="text-sm text-gray-500">Tambah, ubah, hapus, dan atur publikasi materi.</p>
      </div>
      <div class="space-x-2">
        <button id="btn-refresh" class="inline-flex items-center px-3 py-2 rounded-md bg-white text-gray-700 border hover:bg-gray-50 shadow-sm">Refresh</button>
        <button id="btn-add" class="inline-flex items-center px-3 py-2 rounded-md bg-sky-600 text-white hover:bg-sky-700 shadow">+ Tambah Materi</button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
        <input id="q" type="text" placeholder="Cari judul, ringkasan..." class="w-full px-3 py-2 border rounded-md focus:ring-sky-500 focus:border-sky-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
        <input id="tahun" type="number" min="2000" max="2100" class="w-full px-3 py-2 border rounded-md focus:ring-sky-500 focus:border-sky-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Publik</label>
        <select id="publik" class="w-full px-3 py-2 border rounded-md focus:ring-sky-500 focus:border-sky-500">
          <option value="">Semua</option>
          <option value="1">Ya</option>
          <option value="0">Tidak</option>
        </select>
      </div>
    </div>

    <div id="grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach (($materi ?? []) as $m) { ?>
      <div class="bg-white rounded-lg shadow p-4 flex flex-col">
        <div class="flex-1">
          <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1"><?= htmlspecialchars($m['judul'] ?? '') ?></h3>
          <p class="text-xs text-gray-500 mb-2">Periode: <?= htmlspecialchars(($m['bulan'] ?? '').' '.($m['tahun'] ?? '')) ?></p>
          <div class="text-sm text-gray-700 line-clamp-3 mb-3"><?= htmlspecialchars(mb_substr($m['ringkasan'] ?? '', 0, 120)) ?></div>
        </div>
        <div class="mt-3 flex items-center justify-between">
          <span class="text-xs px-2 py-0.5 rounded <?= (($m['publik'] ?? 0) == 1) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>"><?= (($m['publik'] ?? 0) == 1) ? 'Publik' : 'Privat' ?></span>
          <div class="space-x-2">
            <button class="text-sky-700 hover:underline" data-action="edit" data-id="<?= (int) $m['id'] ?>">Edit</button>
            <button class="text-red-600 hover:underline" data-action="delete" data-id="<?= (int) $m['id'] ?>">Hapus</button>
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
      <h3 id="modal-title" class="font-semibold">Tambah Materi</h3>
      <button id="modal-close" class="text-gray-500 hover:text-gray-700">✕</button>
    </div>
    <form id="form" class="p-4 space-y-3">
      <input type="hidden" id="id">
      <div>
        <label class="block text-sm font-medium text-gray-700">Judul</label>
        <input id="judul" type="text" class="mt-1 w-full px-3 py-2 border rounded-md" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Ringkasan</label>
        <textarea id="ringkasan" rows="4" class="mt-1 w-full px-3 py-2 border rounded-md"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Bulan</label>
          <input id="bulan" type="text" placeholder="cth: Januari" class="mt-1 w-full px-3 py-2 border rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Tahun</label>
          <input id="tahun_input" type="number" min="2000" max="2100" class="mt-1 w-full px-3 py-2 border rounded-md" />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">Video URL</label>
          <input id="video_url" type="url" class="mt-1 w-full px-3 py-2 border rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Publik</label>
          <select id="publik_input" class="mt-1 w-full px-3 py-2 border rounded-md">
            <option value="1">Ya</option>
            <option value="0">Tidak</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Versi</label>
        <input id="versi" type="text" class="mt-1 w-full px-3 py-2 border rounded-md" />
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
  // Base detection
  window.APP_BASE = (function(){
    var p = window.location.pathname || '/';
    if (p.startsWith('/pkg/public')) return '/pkg/public';
    if (p.startsWith('/public')) return '/public';
    return '';
  })();

  const grid = document.getElementById('grid');
  const q = document.getElementById('q');
  const tahun = document.getElementById('tahun');
  const publik = document.getElementById('publik');
  const btnAdd = document.getElementById('btn-add');
  const btnRefresh = document.getElementById('btn-refresh');

  const modal = document.getElementById('modal');
  const modalTitle = document.getElementById('modal-title');
  const close = document.getElementById('modal-close');
  const cancel = document.getElementById('btn-cancel');
  const f = (id)=>document.getElementById(id);

  function openModal(title){ modalTitle.textContent=title; modal.classList.remove('hidden'); modal.classList.add('flex'); }
  function closeModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); f('id').value=''; f('judul').value=''; f('ringkasan').value=''; f('bulan').value=''; f('tahun_input').value=''; f('video_url').value=''; f('publik_input').value='1'; f('versi').value=''; }

  function cardHtml(m){
    return `<div class=\"bg-white rounded-lg shadow p-4 flex flex-col\">
      <div class=\"flex-1\">
        <h3 class=\"font-semibold text-gray-900 line-clamp-2 mb-1\">${(m.judul||'')}</h3>
        <p class=\"text-xs text-gray-500 mb-2\">Periode: ${(m.bulan||'')} ${(m.tahun||'')}</p>
        <div class=\"text-sm text-gray-700 line-clamp-3 mb-3\">${(m.ringkasan||'').slice(0,120)}</div>
      </div>
      <div class=\"mt-3 flex items-center justify-between\">
        <span class=\"text-xs px-2 py-0.5 rounded ${ (String(m.publik)==='1') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }\">${ (String(m.publik)==='1') ? 'Publik' : 'Privat' }</span>
        <div class=\"space-x-2\">
          <button class=\"text-sky-700 hover:underline\" data-action=\"edit\" data-id=\"${m.id}\">Edit</button>
          <button class=\"text-red-600 hover:underline\" data-action=\"delete\" data-id=\"${m.id}\">Hapus</button>
        </div>
      </div>
    </div>`;
  }

  function render(list){
    grid.innerHTML = '';
    (list||[]).forEach(m=>{
      const div = document.createElement('div');
      div.innerHTML = cardHtml(m);
      const node = div.firstElementChild;
      grid.appendChild(node);
    });
  }

  async function loadData(){
    const r = await fetch(window.APP_BASE + '/api/materi');
    const data = await r.json();
    window.__ALL_MATERI__ = Array.isArray(data) ? data : [];
    applyFilters();
  }

  function applyFilters(){
    const keyword = (q.value||'').toLowerCase();
    const th = (tahun.value||'').toString();
    const pb = publik.value||'';
    const filtered = (window.__ALL_MATERI__||[]).filter(m=>{
      const matchPublik = !pb || String(m.publik)===String(pb);
      const matchTahun = !th || String(m.tahun)===th;
      const matchKeyword = !keyword || [m.judul, m.ringkasan].filter(Boolean).some(v=>String(v).toLowerCase().includes(keyword));
      return matchPublik && matchTahun && matchKeyword;
    });
    render(filtered);
  }

  async function createItem(payload){
    const r = await fetch(window.APP_BASE + '/api/materi', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    if(!r.ok) throw new Error('Gagal menambah materi');
  }
  async function updateItem(id, payload){
    const r = await fetch(window.APP_BASE + '/api/materi/'+id, { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    if(!r.ok) throw new Error('Gagal mengubah materi');
  }
  async function deleteItem(id){
    const r = await fetch(window.APP_BASE + '/api/materi/'+id, { method:'DELETE' });
    if(!r.ok) throw new Error('Gagal menghapus materi');
  }

  // events
  btnRefresh.addEventListener('click', loadData);
  q.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); applyFilters(); }});
  tahun.addEventListener('input', applyFilters);
  publik.addEventListener('change', applyFilters);
  btnAdd.addEventListener('click', ()=>{ openModal('Tambah Materi'); });
  close.addEventListener('click', closeModal); cancel.addEventListener('click', closeModal);

  document.getElementById('grid').addEventListener('click', async (e)=>{
    const btn = e.target.closest('button'); if(!btn) return;
    const action = btn.dataset.action; const id = btn.dataset.id;
    if(action==='edit'){
      const item = (window.__ALL_MATERI__||[]).find(x=>String(x.id)===String(id));
      if(!item) return;
      f('id').value=item.id; f('judul').value=item.judul||''; f('ringkasan').value=item.ringkasan||''; f('bulan').value=item.bulan||''; f('tahun_input').value=item.tahun||''; f('video_url').value=item.video_url||''; f('publik_input').value=String(item.publik||'1'); f('versi').value=item.versi||'';
      openModal('Ubah Materi');
    } else if(action==='delete'){
      if(confirm('Yakin hapus materi ini?')){
        try{ await deleteItem(id); await loadData(); alert('Berhasil dihapus'); }catch(err){ alert(err.message||'Gagal menghapus'); }
      }
    }
  });

  document.getElementById('form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const payload = {
      judul: f('judul').value||'',
      ringkasan: f('ringkasan').value||'',
      bulan: f('bulan').value||'',
      tahun: f('tahun_input').value||'',
      video_url: f('video_url').value||'',
      publik: f('publik_input').value||'1',
      versi: f('versi').value||'',
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