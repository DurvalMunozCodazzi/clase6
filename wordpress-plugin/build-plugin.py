import re, shutil, os

SRC = "/home/user/clase6/tarot-app/index.html"
PLUGIN_DIR = "/home/user/clase6/wordpress-plugin/tirada-de-tarot"

with open(SRC) as f:
    src = f.read()

style = re.search(r"<style>(.*?)</style>", src, re.S).group(1)
body = re.search(r"<body>\s*(.*?)\s*</body>", src, re.S).group(1)
script_start = body.index("<script>")
markup = body[:script_start].rstrip()
script = re.search(r"<script>(.*?)</script>", body, re.S).group(1)

# CSS -> assets/css/tarot.css (verbatim, already scoped under #tarotApp)
with open(os.path.join(PLUGIN_DIR, "assets/css/tarot.css"), "w") as f:
    f.write(style.strip() + "\n")

# Markup -> templates/app.php (static HTML fragment, no <script>/<style>)
with open(os.path.join(PLUGIN_DIR, "templates/app.php"), "w") as f:
    f.write("<?php if (!defined('ABSPATH')) exit; ?>\n" + markup + "\n")

# JS -> assets/js/tarot.js
#  - swap the hardcoded relative image path for the URL WordPress localizes in
#  - defer execution until the DOM is ready (script may be enqueued in <head>)
js = script
old_img_var = '  var IMAGE_BASE_URL = "assets/cards/";\n'
assert old_img_var in js, "IMAGE_BASE_URL line not found"
new_img_var = (
    '  var IMAGE_BASE_URL = (typeof TDT_CONFIG !== "undefined" && TDT_CONFIG.imageBaseUrl)\n'
    '    ? TDT_CONFIG.imageBaseUrl\n'
    '    : "assets/cards/";\n'
)
js = js.replace(old_img_var, new_img_var)

assert js.lstrip().startswith("(function () {"), "unexpected script wrapper"
js = js.strip()
assert js.endswith("})();")
inner = js[len("(function () {"):-len("})();")]
js = 'document.addEventListener("DOMContentLoaded", function () {' + inner + "});\n"

with open(os.path.join(PLUGIN_DIR, "assets/js/tarot.js"), "w") as f:
    f.write(js)

# Copy the 78 card images
cards_src = "/home/user/clase6/tarot-app/assets/cards"
cards_dest = os.path.join(PLUGIN_DIR, "assets/images/cards")
count = 0
for fname in os.listdir(cards_src):
    if fname.endswith(".jpg"):
        shutil.copyfile(os.path.join(cards_src, fname), os.path.join(cards_dest, fname))
        count += 1

print("CSS bytes:", len(style))
print("Markup bytes:", len(markup))
print("JS bytes:", len(js))
print("Images copied:", count)
