// Whole-page reduced-motion check: nothing animates, autoplays or scroll-fades.
export default async ({ page, sleep }) => {
  await page.size(1440, 900);
  await page.media(true);
  await page.go('http://panmotors.local/');
  await sleep(1500);
  await page.eval(`document.documentElement.style.scrollBehavior='auto'; const H=document.documentElement.scrollHeight; for (let y=0;y<H;y+=500){ scrollTo(0,y); await new Promise(r=>setTimeout(r,120)); } return 1`);
  await sleep(1000);
  return page.eval(`
    const cs = (e) => getComputedStyle(e);
    const rises = [...document.querySelectorAll('[data-rise],[data-rise-l]')];
    const heroIn = [...document.querySelectorAll('[data-hero-in]')];
    const track = document.querySelector('[data-slider-track]');
    document.querySelector('[data-slider-next]').click();
    const photo = document.querySelector('.pm-showroom__photo');
    return {
      htmlScrollBehavior: cs(document.documentElement).scrollBehavior,
      rises: rises.length + ' elements, hidden: ' + rises.filter(e => cs(e).opacity !== '1').length,
      heroEntrance: heroIn.map(e => cs(e).animationName + '/' + cs(e).opacity).join(' '),
      heroVideo: (() => { const v=document.querySelector('.pm-hero__video'); return { display: cs(v).display, paused: v.paused, t: v.currentTime }; })(),
      marquee: cs(document.querySelector('.pm-marquee__track')).animationName,
      aboutFade: document.getElementById('heritage').style.getPropertyValue('--fade') || '0',
      sliderTransition: track.style.transition,
      liveSrc: [...document.querySelectorAll('video[data-live-video]')].filter(v => v.getAttribute('src')).length,
      showroomTransition: cs(photo).transitionDuration,
      menuTransition: cs(document.getElementById('pm-menu')).transitionDuration,
      tileTransition: cs(document.querySelector('.pm-tile')).transitionDuration,
    };`);
};
