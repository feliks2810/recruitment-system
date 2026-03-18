from PIL import Image, ImageDraw, ImageFont
import os

base_dir = r"c:\xampp\htdocs\recruitment-system\public\images"
icon_path = os.path.join(base_dir, "icon-removebg-preview.png")

# 1. Resize Icons
def create_icon(size, out_name):
    img = Image.open(icon_path).convert("RGBA")
    # Resize keeping aspect ratio
    img.thumbnail((size, size), Image.Resampling.LANCZOS)
    
    # Create new square transparent image
    new_img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    # Paste centered
    new_img.paste(img, ((size - img.width) // 2, (size - img.height) // 2))
    new_img.save(os.path.join(base_dir, out_name))
    
    # If size is 192, also save as favicon.ico in the public root folder
    if size == 192:
        public_dir = r"c:\xampp\htdocs\recruitment-system\public"
        new_img.save(os.path.join(public_dir, "favicon.ico"), format="ICO", sizes=[(32, 32)])

create_icon(192, "icon-192x192.png")
create_icon(512, "icon-512x512.png")

# 2. Create Screenshots
def create_screenshot(width, height, factor, out_name):
    img = Image.new("RGB", (width, height), (243, 244, 246)) # bg-gray-100
    draw = ImageDraw.Draw(img)
    
    # Paste logo in center
    logo = Image.open(icon_path).convert("RGBA")
    logo.thumbnail((width // 2, height // 2), Image.Resampling.LANCZOS)
    img.paste(logo, ((width - logo.width) // 2, (height - logo.height) // 2), logo)
    
    img.save(os.path.join(base_dir, out_name))

create_screenshot(1280, 720, "wide", "screenshot-desktop.png")
create_screenshot(720, 1280, "narrow", "screenshot-mobile.png")

print("Assets created successfully")
