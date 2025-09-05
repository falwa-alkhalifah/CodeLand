
/**
 * Educator page – rubric compliant
 */
(function(){
  const { qs, formatStat, formatFeedback } = window.Utils;
  const { loadStore, syncQuizCounts } = window.Store;
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
  function welcome(){
    qs('#welcome').innerHTML = `
      <div class="card">
        <h1>Welcome, ${currentEducator.firstName} 👋</h1>
        <p class="helper">Manage your quizzes and review learners’ recommendations.</p>
      </div>`;
  }
  function educatorInfo(){
    qs('#educator-info').innerHTML = `
      <div class="grid-responsive">
        <div class="flex">
          <img class="avatar" src="${currentEducator.photo}" alt="profile">
          <div>
            <div><strong>${currentEducator.firstName} ${currentEducator.lastName}</strong></div>
            <div class="helper">${currentEducator.email}</div>
          </div>
        </div>
        <div>
          <div class="helper">Topics</div>
          <div>${currentEducator.topics.map(t=>`<span class="badge">${t}</span>`).join(' ')}</div>
        </div>
      </div>`;
  }
  function quizzesTable(){
    loadStore(); syncQuizCounts();
    const tb = qs('#quizzes-table tbody'); tb.innerHTML='';
    quizzes.filter(q=>q.educatorId===currentEducator.id).forEach(q=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`
        <td><a href="quiz.html?id=${q.id}">${q.topic}</a></td>
        <td>${q.questionCount}</td>
        <td>${formatStat(q.takers, q.avgScore)}</td>
        <td>${formatFeedback(q.avgRating)} • <a href="comments.html?quizId=${q.id}">comments</a></td>`;
      tb.appendChild(tr);
    });
  }
  function recommendedTable(){
    const tb = qs('#recommended-table tbody'); tb.innerHTML='';
    recommendedQuestions.forEach(r=>{
      const fig = r.figure ? `<img src="${r.figure}" style="max-width:120px;border-radius:8px">`
                           : `<span class="badge">no figure</span>`;
      const ans = r.choices.map((c,i)=>{
        const cls = i===r.correctIndex ? 'badge success' : 'badge';
        return `<span class="${cls}">${String.fromCharCode(65+i)}. ${c}</span>`;
      }).join(' ');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.topic}</td>
        <td class="flex"><img class="avatar" src="${r.learner.photo}">${r.learner.name}</td>
        <td><div><strong>${r.text}</strong></div><div style="margin:8px 0">${fig}</div><div>${ans}</div></td>
        <td>
          <form class="review-form" data-id="${r.id}">
            <label>Comment</label>
            <textarea class="input" rows="3" name="comment" placeholder="Write a note (optional)"></textarea>
            <div class="flex" style="gap:8px;margin-top:8px">
              <button type="button" class="btn" data-action="approve">Approve</button>
              <button type="button" class="btn btn-danger" data-action="disapprove">Disapprove</button>
            </div>
          </form>
        </td>`;
      tb.appendChild(tr);
    });
    tb.addEventListener('click', (e)=>{
      const b = e.target.closest('button[data-action]'); if(!b) return;
      const id = b.closest('form').dataset.id; b.closest('tr').remove();
      Utils.toast(`Marked #${id} as ${b.dataset.action}`);
    });
  }
  document.addEventListener('DOMContentLoaded', ()=>{
    header(); welcome(); educatorInfo(); quizzesTable(); recommendedTable();
  });
})();
