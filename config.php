; <?php exit(); ?>
;; Enable debug messages
;debug = true

;; Database information, including server,
;; username, password, and database.
[database]
server = localhost:3390
username = root
password = test123
database = my_db


;; Map of different actions (give to the script
;; by the user using GET) to different controllers
;; to handle those actions.
[actions]
action1 = StaticPage
action2 = Login
action3 = Register
default = action1


;; List of custom models. Each model is defined using a number of lines. The
;; first line is the options for the model, i.e. whether it is a parent for
;; other models, and whether it can be password-protected. Every line after
;; that is a column in the database.
;;
;; The columns lines determines the format of the database table. Each
;; column is its line with a number of comma-seprated values. The first value
;; must be the name of the column, and the second value must be the type. After
;; that, everything else is option.
;;
;; Optional value: key (whether the column is a key), null (if the
;; column can be NULL), auto_increment (does the column automatically increment),
;; default (default value for the column), and comment (comment for the column).
[models]
User[] = "protect,parent"
User[] = "id,int,key=primary,auto_increment=true"
User[] = "name,varchar(11),key=unique"
User[] = "password,blob"
User[] = "email,varchar(50),null"

Page[] = "parent"
Page[] = "id,int,key=primary,auto_increment=1"
Page[] = "name,varchar(11),key=unique"

;; List of possible groups. Each group is set to a comma-separated
;; list of permissions. See db/Object.php for more information on
;; how to format permissions.
[auth]
admin = *-*-*
editor = Page-*-*
regular = Page-get-*


;; Cache options. Use "action.actionname" replacing actionnname
;; with one of the actions above to enable to disable caching for
;; that action. The expires option is how long until a
;; cache expires in seconds.
[cache]
action.action1 = true
action.action2 = false
action.action3 = false
expires = 3600


;; Custom paths to the cache, templates, log, and uploads.
[paths]
cache = ROOTDIR/cache
templates = ROOTDIR/templates
log = ROOTDIR/log
uploads = ROOTDOR/uploads

