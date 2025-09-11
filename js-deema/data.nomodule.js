
(function(){
  window.currentEducator = {
    id: 1, firstName: "Deema", lastName: "Alfarhoud",
    email: "deema@ksu.edu.sa", photo: "assets/img/avatar-default.svg",
    topics: ["HTML 101","JAVA SCRIPT","PHP BASICS"]
  };
  window.quizzes = [
    { id:101, topic:"HTML 101", educatorId:1, questionCount:2, takers:12, avgScore:78, avgRating:4.2 },
  ];

  window.recommendedQuestions = [
    { id:9001, topic:"HTML 101", learner:{name:"Sara Abdullah.", photo:"images/WebSA.jpeg"},
      figure:null, text:"Which protocol resolves names?",
      choices:["FTP","DNS","SSH","SMTP"], correctIndex:1, status:"Pending" }
  ];
})();
