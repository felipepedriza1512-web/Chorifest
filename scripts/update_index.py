import re

with open(r'c:\laragon\www\Chorifest\Public\index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the article tag with the wrapper and article
pattern = re.compile(r'(<article class="product-card" data-cat="(.*?)">)\s*<div class="product-img">')
replacement = r'<div class="product-card-wrapper" data-cat="\2">\n                \1\n                    <div class="card-reflection"></div>\n                    <div class="product-img">'
new_content = pattern.sub(replacement, content)

# Replace </article> with </article></div>
new_content = new_content.replace('</article>', '</article>\n            </div>')

# Fix disabled class on article wrapper
new_content = new_content.replace('<article class="product-card unavailable"', '<div class="product-card-wrapper unavailable" data-cat="chorizos">\n                <article class="product-card unavailable"')

# Replace JS logic for filtering
js_old = '''const tabs = document.querySelectorAll('.cat-tab');
        const cards = document.querySelectorAll('.product-card');'''
js_new = '''const tabs = document.querySelectorAll('.cat-tab');
        const cards = document.querySelectorAll('.product-card-wrapper');'''
new_content = new_content.replace(js_old, js_new)

# Add 3D JS script before </body>
js_3d = '''
        // 3D Hover Effect for Cards
        document.querySelectorAll('.product-card-wrapper').forEach(wrapper => {
            const card = wrapper.querySelector('.product-card');
            const reflection = wrapper.querySelector('.card-reflection');
            
            wrapper.addEventListener('mousemove', (e) => {
                const rect = wrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = ((y - centerY) / centerY) * -15; 
                const rotateY = ((x - centerX) / centerX) * 15;
                
                card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(20px)`;
                
                if(reflection) {
                    const bgX = (x / rect.width) * 100;
                    const bgY = (y / rect.height) * 100;
                    reflection.style.background = `radial-gradient(circle at ${bgX}% ${bgY}%, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 60%)`;
                    reflection.style.opacity = '1';
                }
            });
            
            wrapper.addEventListener('mouseleave', () => {
                card.style.transition = 'transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.transform = `rotateX(0deg) rotateY(0deg) translateZ(0)`;
                if(reflection) {
                    reflection.style.opacity = '0';
                    reflection.style.background = 'none';
                }
                
                setTimeout(() => {
                    card.style.transition = 'transform 0.2s, box-shadow 0.2s'; 
                }, 500);
            });
            
            wrapper.addEventListener('mouseenter', () => {
                card.style.transition = 'transform 0.1s ease-out'; 
            });
        });
'''
new_content = new_content.replace('</body>', js_3d + '\n</body>')

with open(r'c:\laragon\www\Chorifest\Public\index.php', 'w', encoding='utf-8') as f:
    f.write(new_content)
print('Modified index.php')
