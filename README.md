# vSearch
vSearch - PHP Search indexing system

INSTALL NOTES:

vSoftware Search Engine v0.2
-run search.sql.gz on the database that is to be crawled.
-configure sources/search.php
-run sources/cron/fullindex.php one time.
-start searching!!!!

AS A REMINDER: run sources/cron/updateindex.php periodically to keep things up to date.

SECURITY NOTICE: to protect your webhost please password protect /sources/cron/ folder
					there is no immediate security risk but fullindex.php can be resource intensive
					and cause heightened server load. they are best run from SSH with a CHMOD of 600
					if this is not possible password protecting the directory will do.