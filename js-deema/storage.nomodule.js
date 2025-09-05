
(function(){
  function loadStore(){ try{ const raw = localStorage.getItem('qh_questionsByQuiz'); if(raw){ window.questionsByQuiz = JSON.parse(raw); } }catch(e){} }
  function saveStore(){ try{ localStorage.setItem('qh_questionsByQuiz', JSON.stringify(window.questionsByQuiz)); }catch(e){} }
  function syncQuizCounts(){ if(!window.quizzes||!window.questionsByQuiz) return; window.quizzes.forEach(qz=>{ qz.questionCount = (window.questionsByQuiz[qz.id]||[]).length; }); }
  window.Store = { loadStore, saveStore, syncQuizCounts };
})();
