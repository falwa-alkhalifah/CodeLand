
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
    const qid=Number(getParam('questionId'));
    qs('#cancel-link').href=`quiz.html?id=${quizId}`;
    const form=qs('#question-form');
    const list=window.questionsByQuiz[quizId]||[];
    const q=list.find(x=>x.id===qid);
    if(q){
      form.text.value=q.text;
      form.c0.value=q.choices[0];
      form.c1.value=q.choices[1];
      form.c2.value=q.choices[2];
      form.c3.value=q.choices[3];
      form.correctIndex.value=String(q.correctIndex);
    }
    form.addEventListener('submit', (e)=>{
      e.preventDefault();
      const idx=list.findIndex(x=>x.id===qid);
      if(idx>=0){
        list[idx]={...list[idx],
          text: form.text.value.trim(),
          choices: [form.c0.value, form.c1.value, form.c2.value, form.c3.value],
          correctIndex: Number(form.correctIndex.value),
          figure: null
        };
        saveStore(); syncQuizCounts(); toast('Updated question');
      }
      setTimeout(()=> window.location.href=`quiz.html?id=${quizId}`, 400);
    });
  }
  document.addEventListener('DOMContentLoaded', ()=>{ header(); initForm(); });
})();
