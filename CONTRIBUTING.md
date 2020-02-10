================================================================================
                               General Guidelines
================================================================================
When writing code to contribute please try to follow established conventions in
the code area you are working on. We are aware that the code is not as consistent
as it could be and there is a controlled ongoing refactoring effort.

If you are working in a code area which is fraught with contradictions then please
seek advice on IRC.

Avoid merging if possible, rebase instead. Although you may have push access to
the central repo please do not push without permission.


================================================================================
                                Committing
================================================================================
When committing changes please include a short commit title (no more than 50
characters) along with a longer description is appropriate. If there are DB 
changes then please include the necessary SQL to upgrade an existing installation
in the commit message as well as changes to gazelle.sql for new installations.

Always leave a blank line between the commit title and the commit message, 
include any information you think might be useful in the future.

Example:
```
Add sorting to categories

DB changes:
ALTER TABLE categories ADD COLUMN sort int(10) NOT NULL DEFAULT 0;
```


================================================================================
                                Bug Fixing
================================================================================
Ideally all bugs will be registered in the issue tracker, when commiting a bugfix
please include the issue number in your commit along with a descriptive commit
title.

Example:
```
Fix sorting of torrent categories

Resolves bug 1234
```

================================================================================
                              New Features
================================================================================
When working on a new feature please create a branch in your personal git project
and commit to that branch, this allows us to test changes in isolation and also
allows us to more easily evaluate changes in a shared testing environment. Once
the changes are approved they can be rebased (or merged if necessary) into the
master branch.

Please also try to reuse code when creating new features, if code is to be shared
by multiple "sections" then it can be placed in the common directory. 

It is desirable for new features to be highly configurable; the articles system 
has been repurposed to allow arbitrary text to be placed on many pages, this is 
useful for text which may be out of place if this software is used on another site. 
Consider large sections of text such as user advice or example input to be prime 
configuration candidates.


================================================================================
                                      Notes
================================================================================
No notes for now other than to thank you for reading this guide and for considering
making a contribution to our shared effort.