
(function(){
  window.currentEducator = {
    id: 1, firstName: "Deema", lastName: "Alfarhoud",
    email: "deema@ksu.edu.sa", photo: "images/educatorUser.jpeg",
    topics: ["HTML 101","JAVA SCRIPT","PHP BASICS"]
  };
  window.quizzes = [
    { id:101, topic:"HTML 101", educatorId:1, questionCount:2, takers:12, avgScore:78, avgRating:4.2 },
  ];

  window.recommendedQuestions = [
    { id:9001, topic:"HTML 101", learner:{name:"Sara Abdullah.", photo:"images/WebSA.jpeg"},
      figure:null, text:"Which HTML tag is used to define a paragraph?",
      choices:["section","div","p","article"], correctIndex:2, status:"Pending" }
  ];
})();
