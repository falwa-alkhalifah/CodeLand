
(function(){
  window.currentEducator = {
    id: 1, firstName: "Dr. Amal", lastName: " Ahmed ",
    email: "AmalSS@ksu.edu.sa", photo: "images/educatorUser.jpeg",
    topics: ["HTML 101","JAVA SCRIPT","PHP BASICS"]
  };
  window.quizzes = [
    { id:101, topic:"HTML 101", educatorId:1, questionCount:5, takers:3, avgScore:78, avgRating:4.2 },
  ];

  window.recommendedQuestions = [
    { id:9001, topic:"HTML 101", learner:{name:"Sara Al-Qahtani", photo:"images/WebSA.jpeg"},
      figure:"images/code.png", text:"Which HTML tag is used to insert an image?",
      choices:["<picture>","<img>","<figure>","<src>"], correctIndex:1, status:"Pending" }
  ];
})();
