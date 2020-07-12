
CREATE TABLE  enewsletter_maillists (
  maillist_id int(11) NOT NULL AUTO_INCREMENT,
  maillist_name varchar(255) NOT NULL DEFAULT '',
  maillist_description text NOT NULL,
  template_file_name varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (maillist_id)
) AUTO_INCREMENT=1 ;



CREATE TABLE  enewsletter_members (
  subscriber_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) unsigned NOT NULL,
  user_name varchar(120) NOT NULL DEFAULT '',
  user_nick varchar(40) NOT NULL DEFAULT '',
  user_email varchar(255) NOT NULL DEFAULT '',
  user_host varchar(120) NOT NULL DEFAULT '',
  user_conf varchar(120) NOT NULL DEFAULT '',
  confirmed enum('0','1') NOT NULL DEFAULT '0',
  activated enum('0','1') NOT NULL DEFAULT '0',
  user_time int(10) DEFAULT '0',
  user_html enum('0','1') NOT NULL DEFAULT '0',
  user_lists varchar(255) NOT NULL DEFAULT '1',
  PRIMARY KEY (subscriber_id),
  UNIQUE KEY `user_email` (`user_email`)
)  AUTO_INCREMENT=1 ;



CREATE TABLE  enewsletter_messages (
  user_id int(8) unsigned NOT NULL DEFAULT '0',
  sent_to int(8) unsigned NOT NULL DEFAULT '0',
  fail_to int(8) unsigned NOT NULL DEFAULT '0',
  mess_id int(8) unsigned NOT NULL AUTO_INCREMENT,
  time_sent int(11) NOT NULL DEFAULT '0',
  message text NOT NULL,
  subject text NOT NULL,
  mess_from text NOT NULL,
  list_template varchar(255) NOT NULL DEFAULT '',
  is_template enum('0','1') NOT NULL DEFAULT '0',
  mail_format enum('0','1') NOT NULL DEFAULT '0',
  mail_type enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (mess_id)
) AUTO_INCREMENT=1 ;



CREATE TABLE  enewsletter_nletters (
  nletter_id int(11) NOT NULL AUTO_INCREMENT,
  nletter_name varchar(100) NOT NULL,
  nletter_description varchar(1000) NOT NULL,
  nletter_template text NOT NULL,
  nletter_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (nletter_id)
) AUTO_INCREMENT=1 ;



CREATE TABLE  enewsletter_nletter_user_link (
  nletter_linkid int(11) NOT NULL AUTO_INCREMENT,
  nletter_id int(11) NOT NULL,
  subscriber_id int(11) NOT NULL,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (nletter_linkid),
  UNIQUE KEY `nletter_id` (`nletter_id`,`subscriber_id`)
) AUTO_INCREMENT=1 ;
