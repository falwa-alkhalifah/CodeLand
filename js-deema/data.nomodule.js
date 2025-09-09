
(function(){
  window.currentEducator = {
    id: 1, firstName: "Deema", lastName: "Alfarhoud",
    email: "deema@ksu.edu.sa", photo: "assets/img/avatar-default.svg",
    topics: ["Networking","Web","AI"]
  };
  window.quizzes = [
    { id:101, topic:"HTML", educatorId:1, questionCount:2, takers:12, avgScore:78, avgRating:4.2 },
    { id:102, topic:"JAVA",        educatorId:1, questionCount:0, takers:0,  avgScore:null, avgRating:null },
    { id:103, topic:"DATABASES",         educatorId:1, questionCount:1, takers:7,  avgScore:85,  avgRating:4.6 }
  ];
  window.questionsByQuiz = {
    101: [
      { id:5001, text:"What is DNS?", figure:null,
        choices:["Name→IP mapping","Routing","Encryption","Compression"], correctIndex:0 },
      { id:5002, text:"Which layer handles routing?", figure:null,
        choices:["Physical","Network","Transport","Application"], correctIndex:1 }
    ],
    102: [],
    103: [
      { id:5101, text:"Which is a supervised algorithm?", figure:null,
        choices:["K-Means","Decision Tree","Apriori","DBSCAN"], correctIndex:1 }
    ]
  };
  window.recommendedQuestions = [
    { id:9001, topic:"Networking", learner:{name:"Sara A.", photo:"assets/img/avatar-default.svg"},
      figure:null, text:"Which protocol resolves names?",
      choices:["FTP","DNS","SSH","SMTP"], correctIndex:1, status:"Pending" }
  ];
})();
