    </div><!-- .dashboard-content -->
</div><!-- .main-content -->

<script>
(function () {
  const themeToggle = document.getElementById('themeToggle');
  const body = document.body;
  const saved = localStorage.getItem('adminTheme');
  if (saved === 'dark') {
    body.classList.add('dark-mode');
    if (themeToggle) themeToggle.textContent = '☀️';
  }
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      body.classList.toggle('dark-mode');
      const isDark = body.classList.contains('dark-mode');
      themeToggle.textContent = isDark ? '☀️' : '🌙';
      localStorage.setItem('adminTheme', isDark ? 'dark' : 'light');
    });
  }
})();
</script>
</body>
</html>