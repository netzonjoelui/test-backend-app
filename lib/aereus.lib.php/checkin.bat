if "%1" == "update" GOTO UPDATE
if "%1" == "add" GOTO ADD

:: Default checkin
svn ci -m ""
GOTO END

:: Update local repo
:UPDATE
svn update
GOTO END

:: Add file to cvs
:ADD
svn add %2
GOTO END

:END

