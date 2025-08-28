document.addEventListener('DOMContentLoaded', () => {
  const isAdmin = window.location.pathname.includes('/admin/');
  const redirectTo = isAdmin ? '/admin/index.php' : '/public/index.html';
  setTimeout(() => { window.location.href = redirectTo; }, 2200);
});

