Controllers in ANT are similar to any MVC type controller. They must extend the base type, Controller, and you may NOT define the contructor. The constructor in the base class will set local private variables $ant and $user which will be active references to the current ant account, along with a handle to the active database for that account, and to the current user.

Controllers MUST be names [controllername]Controller. For intance, to add a new controller with a name 'Test' the following steps must be followed.

1. A new file in /controllers will be created called "TestController.php".
2. Inside /controllers/TestController.php a new class called 'TestController' which extends 'Controller' will be defined
3. To load a function you would enter /controller/Test/functionName in the browser. The controller router will add '*Controller' to the class name when loading.
