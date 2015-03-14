
title  : Blog
layout : blog/index

===


{% $page->set('articles', $page->pages('articles')->visible()->sort('$p->meta("created")', 'desc')) %}

