
title  : Blog
layout : blog/index

===


{% $page->set('articles', $page->pages('articles')->sort('$p->meta("created")', 'desc')) %}

