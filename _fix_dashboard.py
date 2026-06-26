path = r"D:\ProyekTA\resources\views\dashboard\index.blade.php"
with open(path, "rb") as f:
    data = f.read()

role_pos = data.find(b"pa_staff")
while True:
    ctx = data[role_pos-5:role_pos+1]
    if ctx == b"role(":
        break
    role_pos = data.find(b"pa_staff", role_pos + 1)
    if role_pos == -1:
        raise Exception("Not found")

print("Role at:", role_pos)
comment_start = data.rfind(b"{{--", max(0, role_pos - 200), role_pos)
print("Comment at:", comment_start)
endrole_pos = data.find(b"@endrole", role_pos)
print("Endrole at:", endrole_pos)
next_start = endrole_pos + len(b"@endrole")
while next_start < len(data) and data[next_start:next_start+1] in (b"\r", b"\n"):
    next_start += 1

print(f"Replacing {comment_start} to {next_start}")

# Build new content
lines = []
lines.append("    {{-- --- PA Staff --- }}")
lines.append("    @role(" + chr(39) + "pa_staff" + chr(39) + ")")
lines.append("      {{-- Welcome Banner - menghilangkan space putih atas --}}")
lines.append("      <div class=\"md:col-span-3 bg-gradient-to-r from-primary-dark to-primary rounded-2xl p-6 text-white shadow-lg\">")
lines.append("        <h1 class=\"text-2xl font-bold\">Selamat datang, {{ auth()->user()->name }}</h1>")
lines.append("        <p class=\"text-blue-200 mt-1\">")
lines.append("          {{ auth()->user()->institution?->name ?? " + chr(39) + "Sistem SiPadu" + chr(39) + " }}")
lines.append("          &amp;nbsp;&middot;&amp;nbsp;")
lines.append("          <span class=\"bg-white/20 rounded-full px-2 py-0.5 text-xs font-medium\">PA Staff</span>")
lines.append("        </p>")
lines.append("      </div>")
lines.append("")
lines.append("      {{-- Kelola Konten Card --}}")
lines.append("      <a href=\"{{ route(" + chr(39) + "dashboard.staff.cms.index" + chr(39) + ") }}\"")
lines.append("         class=\"bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-primary hover:bg-blue-50 transition group\">")
lines.append("        <div class=\"w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center group-hover:bg-indigo-200 transition\">")
lines.append("          <i class=\"fas fa-layer-group text-indigo-600 text-xl\"></i>")
lines.append("        </div>")
lines.append("        <div>")
lines.append("          <p class=\"font-semibold text-gray-800\">Kelola Konten</p>")
lines.append("          <p class=\"text-xs text-gray-500\">Beranda, Tentang &amp;amp; Berita</p>")
lines.append("        </div>")
lines.append("      </a>")

# Read existing cards from old section
old_section = data[comment_start:next_start]
card_start = old_section.find(b"<a href=")
while card_start >= 0:
    card_end = old_section.find(b"</a>", card_start) + 4
    if card_end > 0:
        lines.append(old_section[card_start:card_end].decode("utf-8", errors="replace"))
    card_start = old_section.find(b"<a href=", card_end)

lines.append("    @endrole")

new_content = chr(13).join(lines).encode("utf-8")
new_data = data[:comment_start] + new_content + data[next_start:]

with open(path, "wb") as f:
    f.write(new_data)

print("Done! New size:", len(new_data))