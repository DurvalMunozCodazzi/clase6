<?php defined('ABSPATH') || exit; ?>
<div class="wrap lls-wrap">
  <h1 class="lls-title"><span class="lls-logo">⚙️</span> Configuración</h1>
  <div class="lls-form-wrap" style="max-width:600px">
    <form method="post" class="lls-form">
      <?php wp_nonce_field('lls_settings', 'lls_settings_nonce'); ?>
      <div class="lls-field">
        <label class="lls-label">Endpoint de verificación (solo lectura)</label>
        <input type="text" class="lls-input" value="<?= esc_url(rest_url('luna-licenses/v1/verify')) ?>" readonly onclick="this.select()">
        <span class="lls-hint">Este es el URL que debe configurarse en el plugin Luna Workspace del cliente.</span>
      </div>
      <div class="lls-field">
        <label class="lls-label">HMAC Secret</label>
        <input type="text" class="lls-input lls-mono" name="hmac_secret" value="<?= esc_attr($hmac) ?>">
        <span class="lls-hint">Debe coincidir con la constante <code>HMAC_SECRET</code> en el plugin Luna Workspace.</span>
      </div>
      <div class="lls-form-footer">
        <button type="submit" class="lls-btn lls-btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
