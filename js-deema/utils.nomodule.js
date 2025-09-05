
(function(){
  function qs(sel, root=document){ return root.querySelector(sel); }
  function getParam(name){ const u = new URL(window.location.href); return u.searchParams.get(name); }
  function toast(msg, ms=1400){ const t = qs('#toast'); if(!t) return alert(msg); t.textContent = msg; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'), ms); }
  function formatStat(takers, avg){ if(!takers) return 'quiz not taken yet'; return `${takers} taker(s) • avg ${avg}%`; }
  function formatFeedback(r){ if(!r) return 'no feedback yet'; return `★ ${Number(r).toFixed(1)}`; }
  window.Utils = { qs, getParam, toast, formatStat, formatFeedback };
})();
