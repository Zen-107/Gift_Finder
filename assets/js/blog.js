const POSTS = [
  { slug: 'gifts-for-nature-lovers', title: '10 Gifts for Nature Lovers', excerpt: 'Ideas for people who love the outdoors.', content: 'Curated picks: yoga mats, eco bottles, plant kits, and more.' },
  { slug: 'gifts-by-zodiac', title: 'Choose Gifts by Zodiac', excerpt: 'Fun ways to match gifts with zodiac signs.', content: 'Lighthearted suggestions to spark inspiration.' },
  { slug: 'under-500-bht', title: 'Gifts under 500 THB that look premium', excerpt: 'Budget-friendly yet classy.', content: 'Books, accessories, and handmade items that impress.' },
];

function isListPage(){ return !!document.getElementById('blogList'); }

document.addEventListener('DOMContentLoaded', ()=>{
  if(isListPage()){
    const list = document.getElementById('blogList');
    list.innerHTML = POSTS.map(p=>`
      <a class="card" href="blog-post.html?slug=${p.slug}">
        <div class="card-body">
          <strong>${p.title}</strong>
          <div>${p.excerpt}</div>
        </div>
      </a>
    `).join('');
  } else {
    const slug = new URLSearchParams(location.search).get('slug');
    const post = POSTS.find(p=>p.slug===slug) || POSTS[0];
    const mount = document.getElementById('post');
    mount.innerHTML = `
      <h1>${post.title}</h1>
      <p>${post.content}</p>
    `;
  }
});


