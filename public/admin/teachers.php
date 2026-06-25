<?php $header = file_get_contents(__DIR__.'/../header.php');
echo $header; ?>

<main class="container mt-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Teachers</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Kelola Guru</h1>
    <a href="/admin" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
  </div>

  <div class="alert alert-info">
    Halaman Kelola Guru sedang dalam pengembangan. Sementara ini, navigasi sudah tersedia.
  </div>

  <div class="card">
    <div class="card-body">
      <p class="mb-0">Silakan kembali lagi nanti untuk fitur lengkap CRUD guru.</p>
    </div>
  </div>
</main>

<?php /* footer removed: use shared header only, consistent with other admin pages */ ?>