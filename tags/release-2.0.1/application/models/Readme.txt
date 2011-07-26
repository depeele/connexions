Following the tagschema suggestions by Nitin Barwankar at:
    - http://tagschema.com/blogs/tagschema/
        - 2005/05/slicing-and-dicing-data-20-part-2.html
        - 2005/10/many-dimensions-of-relatedness-in.html
 
This solution uses three data tables, three association tables and one fact
table as opposed to the single data table and single fact table used by
freetag.

If it is true that SQL joins provide quick data access, then the additional
association tables should remove any bottlenecks and improve performance.
Particularly when one or more dimensions (user, item, tag) grow large.

There are three basic entities, each with its own data table:
    - user        userId, name, ...
    - tag         tagId,  name, ...
    - item        itemId, url,  ...

Each pair has an association table that may contain additional information
about the association:
 - userItem    userItem    * -> 1 user     |   userId/itemId, name,
               userItem    1 -> 1 item     |   description, rating,
                                           |   isFavorite, isPrivate

 - userTag     userTag     * -> 1 user     |   userId/tagId,
               userTag     1 -> 1 tag      |   isFavorite

 - itemTag     itemTag     1 -> 1 tag      |   itemId/tagId
               itemTag     * -> 1 item     |

The whole combination has a fact table that is a 3-way association:
 - userItemTag userItemTag * -> 1 user     |   userId/itemId/tagId
               userItemTag * -> 1 item     |
               userItemTag 1 -> 1 tag      |


Given these, Nitin suggests the following (paraphrased):
  Let the letter i, t, and u represent a specific 'item', 'tag', and 'user'
  respectively.
  Let the upper case I, T, and U represent mappings as follows:
    Let U(i) be all users of item i (with id == x):
            SELECT u.* FROM user u, userItem ui
                   WHERE u.id = ui.userId AND ui.itemId = x
    Let U(t) be all users of tag t (with id == x):
            SELECT u.* FROM user u, userTag ut
                   WHERE u.id = ut.userId AND ut.tagId = x
Similarly:
    Let I(u) be all items of   user u
    Let I(t) be all items with tag t
    Let T(u) be all tags  of   user u
    Let T(i) be all tags  of   item i

Now, we can combine these like T(U(t)) to be the set of all tags of all
users of a single tag t (with id == x):
            SELECT t.* FROM tag t, userTag ut
                   WHERE ut.userId in
                    (SELECT userId from userTag where tagId = x)

Further, U, T, and I are idempotent.  That is, U(U()) == U()

Relatedness:
  items:
    I(T(i)) tag related items                   (i.e. all items of all tags
                                                 of item i).
    I(U(i)) user related items                  (i.e. all items of all
                                                 users of item i).
    U(T(i)) tag related users for item i        (i.e. users who tagged this
                                                 item in a similar manner -
                                                 the T-cluster of users for
                                                 item i.  All users of all
                                                 tags of item i).
    T(U(i)) user related tags for item i        (i.e. all tags of all users
                                                 who have tagged this item.
                                                 the collective tag-wisdom
                                                 about this item).
  tags:
    T(U(t)) user related tags                   (i.e. all tags of all users
                                                 that have a tag t).
    T(I(t)) item related tags                   (i.e. all tags of all items
                                                 having tag t).
    U(I(t)) item related user for tag t         (i.e. users who have items
                                                 with this tag - I-cluster
                                                 of users for tag t).
    I(U(t)) user related items for tag t        (i.e. all items of all
                                                 users with this tag -
                                                 items you might find
                                                 interesting if you have
                                                 tag t).

  users:
    U(T(u)) tag related users of user u         (i.e. all users of all tags
                                                 of user u).
    U(I(u)) item related users of user u        (i.e. all users of all
                                                 items of user u).
    I(T(u)) all items with all tags of user u   (i.e. all items of all tags
                                                 of user u - the T-cluster
                                                 of items for user u).
    T(I(u)) all tags of all items of user u     (i.e. the I-cluster of tags
                                                 for user u).
