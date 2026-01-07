	<!DOCTYPE html>
	<html lang="es">
	<head>
	    <meta charset="UTF-8">
	    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	    <title><?= $title ?? 'Dashboard' ?> - Pulse App</title>
	    <link rel="stylesheet" href="<?= url_path() ?>/public/assets/css/style.css">
	</head>
	<body>
	    <div id="toast" class="toast">Notificación de sistema</div>
	    <!-- Notificaciones Flash -->
	    <?php
	    $session = new PulseSessions();
	    $flash = $session->getFlash();
	    if ($flash): ?>
	        <script>
	            document.addEventListener('DOMContentLoaded', function() {
	                showToast('<?= addslashes($flash['message']) ?>', '<?= $flash['type'] ?>');
	            });
	        </script>
	    <?php endif; ?>
	    <div id="dashboard-view">
	        <!-- Sidebar -->
	        <aside class="sidebar">
	            <div class="brand">
	                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
	                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
	                </svg>
	                <div style="margin-left: 10px;">
	                    <span style="display:block; font-size:14px;">Pulse App</span>
	                    <span class="version">v2.1</span>
	                </div>
	            </div>
				<nav class="nav-menu">
	                <?php if(isset($menu) && !empty($menu)): ?>
	                    <?php foreach($menu as $item): ?>
	                        <!-- CORRECCIÓN: Usar $item['clave'] en lugar de $item->clave -->
	                        <a href="<?= url_path($item['url']) ?>" 
	                        class="nav-item <?= (basename($_SERVER['REQUEST_URI']) == $item['url']) ? 'active' : '' ?>">
	                            <?= $item['icon'] ?>
	                            <span><?= $item['nombre'] ?></span>
	                        </a>
	                    <?php endforeach; ?>
	                <?php else: ?>
	                    <p style="padding: 10px; color: var(--text-muted); font-size: 12px;">Sin módulos asignados</p>
	                <?php endif; ?>
	            </nav>
	            <div class="sidebar-footer">
	                <div class="user-info">
	                    <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
	                    <div class="user-details">
	                        <span class="user-name"><?= htmlspecialchars($user['name'] ?? 'Usuario') ?></span>
	                        <span class="role-badge"><?= ucfirst($user['role_name'] ?? 'viewer') ?></span>
	                    </div>
	                </div>
	                <button onclick="logout()" class="logout-btn" title="Cerrar sesión">
	                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
	                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
	                        <polyline points="16,17 21,12 16,7"/>
	                        <line x1="21" y1="12" x2="9" y2="12"/>
	                    </svg>
	                </button>
	            </div>
	        </aside>
	        <!-- Main Content -->
	        <main class="main-content">
	            <header class="dashboard-header">
	                <button class="sidebar-toggle" onclick="toggleSidebar()">
	                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
	                        <line x1="3" y1="6" x2="21" y2="6"/>
	                        <line x1="3" y1="12" x2="21" y2="12"/>
	                        <line x1="3" y1="18" x2="21" y2="18"/>
	                    </svg>
	                </button>
	                <h1><?= $title ?? 'Dashboard' ?></h1>
	            </header>
	            <div class="dashboard-content">
	                <?= $content ?? '' ?>
	            </div>
	        </main>
	    </div>
	    <script>
	        function toggleSidebar() {
	            const sidebar = document.querySelector('.sidebar');
	            const width = window.innerWidth;
	            if (width <= 768) {
	                sidebar.classList.toggle('active');
	            } else {
	                sidebar.classList.toggle('collapsed');
	            }
	        }
	        function logout() {
	            if(confirm("¿Estás seguro de que deseas cerrar sesión?")) {
	                window.location.href = '<?= url_path() ?>auth/logout';
	            }
	        }
	    </script>
	</body>
	</html>