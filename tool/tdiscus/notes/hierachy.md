
Hierarchy Design
----------------

I did some research and settled on a closure table versus a nested set for tdiscus.  I felt like 
nested sets were too costly to maintain on insert and threaded discssions do a lot of inserting.
Also you can augment the closure table with some pre-computed columns (like level) to avoid some
of the costly operations.  Also I wanted flexibility on sorting which is toughter in nested sets.

We will see how it goes.  A good comparison:

http://blog.zaletskyy.com/hierarchical-storage-of-data-in-databases

References
----------

https://stackoverflow.com/questions/192220/what-is-the-most-efficient-elegant-way-to-parse-a-flat-table-into-a-tree/192462#192462

https://www.slideshare.net/billkarwin/models-for-hierarchical-data

https://stackoverflow.com/questions/8252323/mysql-closure-table-hierarchical-database-how-to-pull-information-out-in-the-c

