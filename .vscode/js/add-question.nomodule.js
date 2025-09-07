
(function(){
  const { qs, getParam, toast } = window.Utils;
  const { loadStore, saveStore, syncQuizCounts } = window.Store;
  function header(){
    const h=document.querySelector('.header-user');
    h.innerHTML=`
      <div class="brand"><img class="logo-img" src="assets/img/logo-codeland.png" alt="Codeland"> Codeland</div>
      <div class="flex">
        <img class="avatar" src="${currentEducator.photo}" alt="avatar">
        <span>${currentEducator.firstName}</span>
        <a class="btn-outline" href="index.html">Sign out</a>
      </div>`;
  }
  function initForm(){
    loadStore();
    const quizId=Number(getParam('quizId'))||101;
    qs('#cancel-link').href=`quiz.html?id=${quizId}`;
    const form=qs('#question-form');
    form.addEventListener('submit', (e)=>{
      e.preventDefault();
      const payload={
        id: Math.floor(Math.random()*90000)+10000,
        text: form.text.value.trim(),
        choices: [form.c0.value, form.c1.value, form.c2.value, form.c3.value],
        correctIndex: Number(form.correctIndex.value),
        figure: null
      };
      if(!window.questionsByQuiz[quizId]) window.questionsByQuiz[quizId]=[];
      window.questionsByQuiz[quizId].push(payload);
      saveStore(); syncQuizCounts(); toast('Added question');
      setTimeout(()=> window.location.href=`quiz.html?id=${quizId}`, 400);
    });
  }
  document.addEventListener('DOMContentLoaded', ()=>{ header(); initForm(); });
})();
