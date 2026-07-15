<?php
// admin/views/license-required.php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <div style="max-width:500px;margin:60px auto;text-align:center;padding:40px;background:#fff;border:1px solid #e0e0e0;border-radius:10px;">
        <div style="font-size:48px;margin-bottom:16px;">🔒</div>
        <h2 style="color:#c62828;">Licencia inactiva</h2>
        <p style="color:#555;">Necesitás activar tu licencia anual para usar ReservaTotal.</p>
        <a href="<?php echo esc_url( admin_url('admin.php?page=reservatotal-licencia') ); ?>"
           class="button button-primary button-hero" style="margin-top:12px;">
            Activar licencia →
        </a>
        <p style="margin-top:20px;font-size:13px;color:#888;">
            ¿No tenés licencia? <a href="https://reservatotal.com.ar" target="_blank">Comprala en reservatotal.com.ar</a>
        </p>
    </div>
</div>
<?php
// Forzar die para que no continúe cargando el resto de la página
if ( ! headers_sent() ) die();
