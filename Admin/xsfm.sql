SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL,
`data` longtext NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='系统配置表';
INSERT INTO `config` VALUES ('1', 'DISABLE_CAPTCHA','i:0;');
-- INSERT INTO `config` VALUES ('2', 'admin_show','s:27:"baodan_wuliu_pro,kuaidi_pro";');

DROP TABLE IF EXISTS `area`;
CREATE TABLE `area` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `地区编码` varchar(255) DEFAULT NULL,
  `上级编码` varchar(255) DEFAULT NULL,
  `地区名称` varchar(255) DEFAULT NULL,
  `地区级别` varchar(255) DEFAULT NULL,
  `是否末级` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `地区编码` (`地区编码`) USING BTREE,
  KEY `id` (`id`) USING BTREE,
  KEY `上级编码` (`上级编码`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=257735 DEFAULT CHARSET=utf8 COMMENT='地区列表';

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
`id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
`account` varchar(64) NOT NULL DEFAULT '',
`nickname` varchar(50) NOT NULL DEFAULT '',
`password` varchar(255) NOT NULL DEFAULT '',
`last_login_time` int(10) unsigned NOT NULL DEFAULT '0',
`last_login_ip` varchar(40) NOT NULL DEFAULT '',
`login_count` mediumint(8) unsigned NOT NULL DEFAULT '0',
`email` varchar(50) NOT NULL DEFAULT '',
`remark` varchar(255) NOT NULL DEFAULT '',
`create_time` int(10) unsigned NOT NULL DEFAULT '0',
`update_time` int(10) unsigned NOT NULL DEFAULT '0',
`status` tinyint(1) NOT NULL DEFAULT '0',
`admin_status` tinyint(1) NOT NULL DEFAULT '0',
`googlepass` varchar(50) NOT NULL DEFAULT '',
PRIMARY KEY (`id`),
UNIQUE KEY `account` (`account`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员表';

DROP TABLE IF EXISTS `admin_access`;
CREATE TABLE `admin_access` (
`admin_id` smallint(6) unsigned NOT NULL DEFAULT '0',
`node_id` smallint(6) unsigned NOT NULL DEFAULT '0',
`level` tinyint(1) NOT NULL DEFAULT '0',
`pid` smallint(6) NOT NULL DEFAULT '0',
KEY `adminId` (`admin_id`),
KEY `nodeId` (`node_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员授权表';

DROP TABLE IF EXISTS `node`;
CREATE TABLE `node` (
`id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL DEFAULT '',
`group` varchar(20) NOT NULL DEFAULT '' COMMENT '分组',
`title` varchar(255) NOT NULL DEFAULT '',
`args` varchar(255) NOT NULL DEFAULT '',
`remark` varchar(255) NOT NULL DEFAULT '',
`sort` smallint(6) unsigned NOT NULL DEFAULT '0',
`pid` smallint(6) unsigned NOT NULL DEFAULT '0',
`level` tinyint(1) unsigned NOT NULL DEFAULT '0',
`status` tinyint(1) NOT NULL DEFAULT '0',
`is_sync_node` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否同步节点数据',
`is_sync_menu` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否同步菜单数据',
`is_quick_search` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启快捷搜索',
`type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1会员节点,0管理员节点',
`parent` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单上级',
`setParent` varchar(255) NOT NULL DEFAULT '' COMMENT '权限设置的显示上级',
PRIMARY KEY (`id`),
KEY `level` (`level`),
KEY `pid` (`pid`),
KEY `name` (`name`),
KEY `name_pid_level` (`name`,`pid`,`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
`id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(20) NOT NULL DEFAULT '',
`pid` smallint(6) NOT NULL DEFAULT '0',
`remark` varchar(255) NOT NULL DEFAULT '',
`ename` varchar(5) NOT NULL DEFAULT '',
`create_time` int(11) unsigned NOT NULL DEFAULT '0',
`update_time` int(11) unsigned NOT NULL DEFAULT '0',
`status` tinyint(1) unsigned NOT NULL DEFAULT '0',
`type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 会员权限组,0管理员权限组',
PRIMARY KEY (`id`),
KEY `parentId` (`pid`),
KEY `ename` (`ename`),
KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='角色表';

DROP TABLE IF EXISTS `role_access`;
CREATE TABLE `role_access` (
`role_id` smallint(6) unsigned NOT NULL DEFAULT '0',
`node_id` smallint(6) unsigned NOT NULL DEFAULT '0',
`level` tinyint(1) NOT NULL DEFAULT '0',
`pid` smallint(6) NOT NULL DEFAULT '0',
KEY `nodeId` (`node_id`),
KEY `roleId` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='角色授权表';

DROP TABLE IF EXISTS `role_admin`;
CREATE TABLE `role_admin` (
`role_id` mediumint(9) unsigned NOT NULL DEFAULT '0',
`admin_id` char(32) NOT NULL DEFAULT '',
KEY `group_id` (`role_id`),
KEY `user_id` (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 
COMMENT='角色管理员关联表';


DROP TABLE IF EXISTS `xtable_set`;
CREATE TABLE `xtable_set` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`标题` varchar(1000) NOT NULL DEFAULT '',
`显示` varchar(255) NOT NULL DEFAULT '',
`地址` varchar(255) NOT NULL DEFAULT '',
`排序` varchar(255) NOT NULL DEFAULT '',
`数组MD5` varchar(50),
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='菜单';

DROP TABLE IF EXISTS `pay_event`;
CREATE TABLE `pay_event` (
`orderid` varchar(255) NOT NULL DEFAULT '',
`event` char(10) NOT NULL DEFAULT '' COMMENT '事件',
`app` varchar(255) NOT NULL DEFAULT '' COMMENT '应用',
`group` varchar(255) NOT NULL DEFAULT '' COMMENT '分组',
`model` varchar(255) NOT NULL DEFAULT '' COMMENT '模型',
`method` varchar(255) NOT NULL DEFAULT '' COMMENT '方法',
`args` varchar(255) NOT NULL DEFAULT '' COMMENT '参数',
`create_time` int(10) unsigned NOT NULL DEFAULT '0',
UNIQUE KEY `orderid` (`orderid`,`event`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `pay_order`;
CREATE TABLE `pay_order` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`orderId` varchar(255) NOT NULL DEFAULT '',
`money` double(10,2) NOT NULL DEFAULT '0',
`realmoney` double(10,2) NOT NULL DEFAULT '0',
`payment` varchar(255) NOT NULL DEFAULT '' COMMENT '支付方式',
`payment_class` varchar(255) NOT NULL DEFAULT '',
`create_time` int(10) NOT NULL DEFAULT '0',
`status` tinyint(1) unsigned NOT NULL DEFAULT '0',
`memo` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
`userid` varchar(50) default NULL DEFAULT '' COMMENT '编号',
`username` varchar(50) default NULL DEFAULT '' COMMENT '姓名',
`type` varchar(20) default NULL DEFAULT '' COMMENT '账户类型',
PRIMARY KEY (`id`)
) ENGINE=innodb DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pay_onlineaccount`;
CREATE TABLE `pay_onlineaccount` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`pay_type` varchar(255) NOT NULL DEFAULT '',
`pay_attr` varchar(500) NOT NULL DEFAULT '',
`pay_name` varchar(255) NOT NULL DEFAULT '',
`pay_amount` double(10,2) NOT NULL DEFAULT '0',
`name` varchar(255) NOT NULL DEFAULT '',
`account` varchar(255) NOT NULL DEFAULT '',
`state` tinyint(1) unsigned NOT NULL DEFAULT '0',
`credit` varchar(12) NOT NULL DEFAULT '',
`time` varchar(11) NOT NULL DEFAULT '',
PRIMARY KEY (`id`)
) ENGINE=innodb DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`admin_id` int(11) NOT NULL DEFAULT '0',
`user_id` varchar(255) NOT NULL DEFAULT '',
`user_type` varchar(255) NOT NULL DEFAULT '',
`target_user_id` varchar(255) NOT NULL DEFAULT '',
`target_user_type` varchar(255) NOT NULL DEFAULT '',
`application` varchar(50) NOT NULL DEFAULT '',
`group` varchar(20) NOT NULL DEFAULT '',
`module` varchar(50) NOT NULL DEFAULT '',
`action` varchar(50) NOT NULL DEFAULT '',
`content` text NOT NULL,
`old_data` text NOT NULL,
`new_data` text NOT NULL,
`post_data` text NOT NULL,
`get_data` text NOT NULL,
`ip` varchar(50) NOT NULL DEFAULT '',
`address` varchar(50) NOT NULL DEFAULT '',
`memo` varchar(50) NOT NULL DEFAULT '',
`create_time` int(11) DEFAULT '0',
PRIMARY KEY (`id`),
KEY `application` (`application`,`module`,`action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `session`;
     CREATE TABLE session (
       session_id varchar(255) NOT NULL DEFAULT '',
       session_expire int(11) NOT NULL DEFAULT '0',
       session_data blob,
       UNIQUE KEY `session_id` (`session_id`)
     );

DROP TABLE IF EXISTS `dms_快递`;
CREATE TABLE `dms_快递` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company` varchar(255) DEFAULT NULL DEFAULT '' COMMENT '公司名称',
  `address` varchar(255) DEFAULT NULL DEFAULT '' COMMENT '公司地址',
  `tel` varchar(255) DEFAULT NULL DEFAULT '' COMMENT '联系电话',
  `contact` varchar(255) DEFAULT NULL DEFAULT '' COMMENT '联系人',
  `url` varchar(255) DEFAULT NULL DEFAULT '' COMMENT '公司网址',
  `addtime` int(11) DEFAULT 0 COMMENT '添加时间',
  `state` varchar(10) DEFAULT '是',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='物流快递信息表';

DROP TABLE IF EXISTS `dms_密保`;
CREATE TABLE `dms_密保` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `编号` varchar(50) DEFAULT '',
  `密保问题` varchar(100) DEFAULT '',
  `密保答案` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`编号`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `yubicloud`;
CREATE TABLE `yubicloud` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`account_id` int(10) NOT NULL DEFAULT '0',
`yubi_prefix` varchar(12) NOT NULL DEFAULT '',
`yubi_prefix_name` varchar(250) NOT NULL DEFAULT '',
`addtime` int(11) DEFAULT 0 COMMENT '添加时间',
`endtime` int(11) DEFAULT 0 COMMENT '失效时间',
`state` tinyint(1) DEFAULT '0',
PRIMARY KEY (`id`),
KEY `account_id` (`account_id`)
) ENGINE=innodb DEFAULT CHARSET=utf8 COMMENT='管理员yubicloud表';

DROP TABLE IF EXISTS `mail_account`;
CREATE TABLE `mail_account` (
	`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '编号',
	`account` VARCHAR(100) NOT NULL COMMENT '账号',
	`password` VARCHAR(100) NOT NULL COMMENT '密码',
	`smtp` VARCHAR(100) NOT NULL COMMENT '接收服务器',
	`port` VARCHAR(10) NOT NULL COMMENT '接收端口',
	`ssl` ENUM('0','1') NULL DEFAULT '0' COMMENT '加密套接字协议层',
	`times` INT(11) NULL DEFAULT NULL COMMENT '发送次数',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;