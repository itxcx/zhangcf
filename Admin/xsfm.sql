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
  `uid` int(11) NOT NULL,
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

DROP TABLE IF EXISTS `dms_langdata`;
CREATE TABLE `dms_langdata` (
	`lid` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(100) NOT NULL COMMENT '语言标签名',
	`loadfile` VARCHAR(100) NOT NULL COMMENT '调用语言标签的文件路径',
	`file` VARCHAR(100) NULL DEFAULT NULL COMMENT '语言标签所在文件名',
	`﻿af` VARCHAR(50) NULL DEFAULT NULL COMMENT '南非语',
	`af-za` VARCHAR(50) NULL DEFAULT NULL COMMENT '南非语',
	`ar` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语',
	`ar-ae` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(阿联酋)',
	`ar-bh` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(巴林)',
	`ar-dz` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(阿尔及利亚)',
	`ar-eg` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(埃及)',
	`ar-iq` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(伊拉克)',
	`ar-jo` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(约旦)',
	`ar-kw` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(科威特)',
	`ar-lb` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(黎巴嫩)',
	`ar-ly` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(利比亚)',
	`ar-ma` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(摩洛哥)',
	`ar-om` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(阿曼)',
	`ar-qa` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(卡塔尔)',
	`ar-sa` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(沙特阿拉伯)',
	`ar-sy` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(叙利亚)',
	`ar-tn` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(突尼斯)',
	`ar-ye` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿拉伯语(也门)',
	`az` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿塞拜疆语',
	`az-az` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿塞拜疆语',
	`be` VARCHAR(50) NULL DEFAULT NULL COMMENT '比利时语',
	`be-by` VARCHAR(50) NULL DEFAULT NULL COMMENT '比利时语',
	`bg` VARCHAR(50) NULL DEFAULT NULL COMMENT '保加利亚语',
	`bg-bg` VARCHAR(50) NULL DEFAULT NULL COMMENT '保加利亚语',
	`bs-ba` VARCHAR(50) NULL DEFAULT NULL COMMENT '波斯尼亚语(拉丁文，波斯尼亚和黑塞哥维那)',
	`ca` VARCHAR(50) NULL DEFAULT NULL COMMENT '加泰隆语',
	`ca-es` VARCHAR(50) NULL DEFAULT NULL COMMENT '加泰隆语',
	`cs` VARCHAR(50) NULL DEFAULT NULL COMMENT '捷克语',
	`cs-cz` VARCHAR(50) NULL DEFAULT NULL COMMENT '捷克语',
	`cy` VARCHAR(50) NULL DEFAULT NULL COMMENT '威尔士语',
	`cy-gb` VARCHAR(50) NULL DEFAULT NULL COMMENT '威尔士语',
	`da` VARCHAR(50) NULL DEFAULT NULL COMMENT '丹麦语',
	`da-dk` VARCHAR(50) NULL DEFAULT NULL COMMENT '丹麦语',
	`de` VARCHAR(50) NULL DEFAULT NULL COMMENT '德语',
	`de-at` VARCHAR(50) NULL DEFAULT NULL COMMENT '德语(奥地利)',
	`de-ch` VARCHAR(50) NULL DEFAULT NULL COMMENT '德语(瑞士)',
	`de-de` VARCHAR(50) NULL DEFAULT NULL COMMENT '德语(德国)',
	`de-li` VARCHAR(50) NULL DEFAULT NULL COMMENT '德语(列支敦士登)',
	`de-lu` VARCHAR(50) NULL DEFAULT NULL COMMENT '德语(卢森堡)',
	`dv` VARCHAR(50) NULL DEFAULT NULL COMMENT '第维埃语',
	`dv-mv` VARCHAR(50) NULL DEFAULT NULL COMMENT '第维埃语',
	`el` VARCHAR(50) NULL DEFAULT NULL COMMENT '希腊语',
	`el-gr` VARCHAR(50) NULL DEFAULT NULL COMMENT '希腊语',
	`en` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语',
	`en-au` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(澳大利亚)',
	`en-bz` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(伯利兹)',
	`en-ca` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(加拿大)',
	`en-cb` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(加勒比海)',
	`en-gb` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(英国)',
	`en-ie` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(爱尔兰)',
	`en-jm` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(牙买加)',
	`en-nz` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(新西兰)',
	`en-ph` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(菲律宾)',
	`en-tt` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(特立尼达)',
	`en-us` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(美国)',
	`en-za` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(南非)',
	`en-zw` VARCHAR(50) NULL DEFAULT NULL COMMENT '英语(津巴布韦)',
	`eo` VARCHAR(50) NULL DEFAULT NULL COMMENT '世界语',
	`es` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语',
	`es-ar` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(阿根廷)',
	`es-bo` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(玻利维亚)',
	`es-cl` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(智利)',
	`es-co` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(哥伦比亚)',
	`es-cr` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(哥斯达黎加)',
	`es-do` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(多米尼加共和国)',
	`es-ec` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(厄瓜多尔)',
	`es-es` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(国际)',
	`es-gt` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(危地马拉)',
	`es-hn` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(洪都拉斯)',
	`es-mx` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(墨西哥)',
	`es-ni` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(尼加拉瓜)',
	`es-pa` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(巴拿马)',
	`es-pe` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(秘鲁)',
	`es-pr` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(波多黎各(美))',
	`es-py` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(巴拉圭)',
	`es-sv` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(萨尔瓦多)',
	`es-uy` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(乌拉圭)',
	`es-ve` VARCHAR(50) NULL DEFAULT NULL COMMENT '西班牙语(委内瑞拉)',
	`et` VARCHAR(50) NULL DEFAULT NULL COMMENT '爱沙尼亚语',
	`et-ee` VARCHAR(50) NULL DEFAULT NULL COMMENT '爱沙尼亚语',
	`eu` VARCHAR(50) NULL DEFAULT NULL COMMENT '巴士克语',
	`eu-es` VARCHAR(50) NULL DEFAULT NULL COMMENT '巴士克语',
	`fa` VARCHAR(50) NULL DEFAULT NULL COMMENT '法斯语',
	`fa-ir` VARCHAR(50) NULL DEFAULT NULL COMMENT '法斯语',
	`fi` VARCHAR(50) NULL DEFAULT NULL COMMENT '芬兰语',
	`fi-fi` VARCHAR(50) NULL DEFAULT NULL COMMENT '芬兰语',
	`fo` VARCHAR(50) NULL DEFAULT NULL COMMENT '法罗语',
	`fo-fo` VARCHAR(50) NULL DEFAULT NULL COMMENT '法罗语',
	`fr` VARCHAR(50) NULL DEFAULT NULL COMMENT '法语',
	`fr-be` VARCHAR(50) NULL DEFAULT NULL COMMENT '法语(比利时)',
	`fr-ca` VARCHAR(50) NULL DEFAULT NULL COMMENT '法语(加拿大)',
	`fr-ch` VARCHAR(50) NULL DEFAULT NULL COMMENT '法语(瑞士)',
	`fr-fr` VARCHAR(50) NULL DEFAULT NULL COMMENT '法语(法国)',
	`fr-lu` VARCHAR(50) NULL DEFAULT NULL COMMENT '法语(卢森堡)',
	`fr-mc` VARCHAR(50) NULL DEFAULT NULL COMMENT '法语(摩纳哥)',
	`gl` VARCHAR(50) NULL DEFAULT NULL COMMENT '加里西亚语',
	`gl-es` VARCHAR(50) NULL DEFAULT NULL COMMENT '加里西亚语',
	`gu` VARCHAR(50) NULL DEFAULT NULL COMMENT '古吉拉特语',
	`gu-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '古吉拉特语',
	`he` VARCHAR(50) NULL DEFAULT NULL COMMENT '希伯来语',
	`he-il` VARCHAR(50) NULL DEFAULT NULL COMMENT '希伯来语',
	`hi` VARCHAR(50) NULL DEFAULT NULL COMMENT '印地语',
	`hi-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '印地语',
	`hr` VARCHAR(50) NULL DEFAULT NULL COMMENT '克罗地亚语',
	`hr-ba` VARCHAR(50) NULL DEFAULT NULL COMMENT '克罗地亚语(波斯尼亚和黑塞哥维那)',
	`hr-hr` VARCHAR(50) NULL DEFAULT NULL COMMENT '克罗地亚语',
	`hu` VARCHAR(50) NULL DEFAULT NULL COMMENT '匈牙利语',
	`hu-hu` VARCHAR(50) NULL DEFAULT NULL COMMENT '匈牙利语',
	`hy` VARCHAR(50) NULL DEFAULT NULL COMMENT '亚美尼亚语',
	`hy-am` VARCHAR(50) NULL DEFAULT NULL COMMENT '亚美尼亚语',
	`id` VARCHAR(50) NULL DEFAULT NULL COMMENT '印度尼西亚语',
	`id-id` VARCHAR(50) NULL DEFAULT NULL COMMENT '印度尼西亚语',
	`is` VARCHAR(50) NULL DEFAULT NULL COMMENT '冰岛语',
	`is-is` VARCHAR(50) NULL DEFAULT NULL COMMENT '冰岛语',
	`it` VARCHAR(50) NULL DEFAULT NULL COMMENT '意大利语',
	`it-ch` VARCHAR(50) NULL DEFAULT NULL COMMENT '意大利语(瑞士)',
	`it-it` VARCHAR(50) NULL DEFAULT NULL COMMENT '意大利语(意大利)',
	`ja` VARCHAR(50) NULL DEFAULT NULL COMMENT '日语',
	`ja-jp` VARCHAR(50) NULL DEFAULT NULL COMMENT '日语',
	`ka` VARCHAR(50) NULL DEFAULT NULL COMMENT '格鲁吉亚语',
	`ka-ge` VARCHAR(50) NULL DEFAULT NULL COMMENT '格鲁吉亚语',
	`kk` VARCHAR(50) NULL DEFAULT NULL COMMENT '哈萨克语',
	`kk-kz` VARCHAR(50) NULL DEFAULT NULL COMMENT '哈萨克语',
	`kn` VARCHAR(50) NULL DEFAULT NULL COMMENT '卡纳拉语',
	`kn-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '卡纳拉语',
	`ko` VARCHAR(50) NULL DEFAULT NULL COMMENT '朝鲜语',
	`ko-kr` VARCHAR(50) NULL DEFAULT NULL COMMENT '朝鲜语',
	`kok` VARCHAR(50) NULL DEFAULT NULL COMMENT '孔卡尼语',
	`kok-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '孔卡尼语',
	`ky` VARCHAR(50) NULL DEFAULT NULL COMMENT '吉尔吉斯语',
	`ky-kg` VARCHAR(50) NULL DEFAULT NULL COMMENT '吉尔吉斯语(西里尔文)',
	`lt` VARCHAR(50) NULL DEFAULT NULL COMMENT '立陶宛语',
	`lt-lt` VARCHAR(50) NULL DEFAULT NULL COMMENT '立陶宛语',
	`lv` VARCHAR(50) NULL DEFAULT NULL COMMENT '拉脱维亚语',
	`lv-lv` VARCHAR(50) NULL DEFAULT NULL COMMENT '拉脱维亚语',
	`mi` VARCHAR(50) NULL DEFAULT NULL COMMENT '毛利语',
	`mi-nz` VARCHAR(50) NULL DEFAULT NULL COMMENT '毛利语',
	`mk` VARCHAR(50) NULL DEFAULT NULL COMMENT '马其顿语',
	`mk-mk` VARCHAR(50) NULL DEFAULT NULL COMMENT '马其顿语(fyrom)',
	`mn` VARCHAR(50) NULL DEFAULT NULL COMMENT '蒙古语',
	`mn-mn` VARCHAR(50) NULL DEFAULT NULL COMMENT '蒙古语(西里尔文)',
	`mr` VARCHAR(50) NULL DEFAULT NULL COMMENT '马拉地语',
	`mr-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '马拉地语',
	`ms` VARCHAR(50) NULL DEFAULT NULL COMMENT '马来语',
	`ms-bn` VARCHAR(50) NULL DEFAULT NULL COMMENT '马来语(文莱达鲁萨兰)',
	`ms-my` VARCHAR(50) NULL DEFAULT NULL COMMENT '马来语(马来西亚)',
	`mt` VARCHAR(50) NULL DEFAULT NULL COMMENT '马耳他语',
	`mt-mt` VARCHAR(50) NULL DEFAULT NULL COMMENT '马耳他语',
	`nb` VARCHAR(50) NULL DEFAULT NULL COMMENT '挪威语(伯克梅尔)',
	`nb-no` VARCHAR(50) NULL DEFAULT NULL COMMENT '挪威语(伯克梅尔)(挪威)',
	`nl` VARCHAR(50) NULL DEFAULT NULL COMMENT '荷兰语',
	`nl-be` VARCHAR(50) NULL DEFAULT NULL COMMENT '荷兰语(比利时)',
	`nl-nl` VARCHAR(50) NULL DEFAULT NULL COMMENT '荷兰语(荷兰)',
	`nn-no` VARCHAR(50) NULL DEFAULT NULL COMMENT '挪威语(尼诺斯克)(挪威)',
	`ns` VARCHAR(50) NULL DEFAULT NULL COMMENT '北梭托语',
	`ns-za` VARCHAR(50) NULL DEFAULT NULL COMMENT '北梭托语',
	`pa` VARCHAR(50) NULL DEFAULT NULL COMMENT '旁遮普语',
	`pa-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '旁遮普语',
	`pl` VARCHAR(50) NULL DEFAULT NULL COMMENT '波兰语',
	`pl-pl` VARCHAR(50) NULL DEFAULT NULL COMMENT '波兰语',
	`pt` VARCHAR(50) NULL DEFAULT NULL COMMENT '葡萄牙语',
	`pt-br` VARCHAR(50) NULL DEFAULT NULL COMMENT '葡萄牙语(巴西)',
	`pt-pt` VARCHAR(50) NULL DEFAULT NULL COMMENT '葡萄牙语(葡萄牙)',
	`qu` VARCHAR(50) NULL DEFAULT NULL COMMENT '克丘亚语',
	`qu-bo` VARCHAR(50) NULL DEFAULT NULL COMMENT '克丘亚语(玻利维亚)',
	`qu-ec` VARCHAR(50) NULL DEFAULT NULL COMMENT '克丘亚语(厄瓜多尔)',
	`qu-pe` VARCHAR(50) NULL DEFAULT NULL COMMENT '克丘亚语(秘鲁)',
	`ro` VARCHAR(50) NULL DEFAULT NULL COMMENT '罗马尼亚语',
	`ro-ro` VARCHAR(50) NULL DEFAULT NULL COMMENT '罗马尼亚语',
	`ru` VARCHAR(50) NULL DEFAULT NULL COMMENT '俄语',
	`ru-ru` VARCHAR(50) NULL DEFAULT NULL COMMENT '俄语',
	`sa` VARCHAR(50) NULL DEFAULT NULL COMMENT '梵文',
	`sa-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '梵文',
	`se` VARCHAR(50) NULL DEFAULT NULL COMMENT '北萨摩斯语',
	`se-fi` VARCHAR(50) NULL DEFAULT NULL COMMENT '萨摩斯语(芬兰)',
	`se-no` VARCHAR(50) NULL DEFAULT NULL COMMENT '萨摩斯语(挪威)',
	`se-se` VARCHAR(50) NULL DEFAULT NULL COMMENT '萨摩斯语(瑞典)',
	`sk` VARCHAR(50) NULL DEFAULT NULL COMMENT '斯洛伐克语',
	`sk-sk` VARCHAR(50) NULL DEFAULT NULL COMMENT '斯洛伐克语',
	`sl` VARCHAR(50) NULL DEFAULT NULL COMMENT '斯洛文尼亚语',
	`sl-si` VARCHAR(50) NULL DEFAULT NULL COMMENT '斯洛文尼亚语',
	`sq` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿尔巴尼亚语',
	`sq-al` VARCHAR(50) NULL DEFAULT NULL COMMENT '阿尔巴尼亚语',
	`sr-ba` VARCHAR(50) NULL DEFAULT NULL COMMENT '塞尔维亚语',
	`sr-sp` VARCHAR(50) NULL DEFAULT NULL COMMENT '塞尔维亚语',
	`sv` VARCHAR(50) NULL DEFAULT NULL COMMENT '瑞典语',
	`sv-fi` VARCHAR(50) NULL DEFAULT NULL COMMENT '瑞典语(芬兰)',
	`sv-se` VARCHAR(50) NULL DEFAULT NULL COMMENT '瑞典语',
	`sw` VARCHAR(50) NULL DEFAULT NULL COMMENT '斯瓦希里语',
	`sw-ke` VARCHAR(50) NULL DEFAULT NULL COMMENT '斯瓦希里语',
	`syr` VARCHAR(50) NULL DEFAULT NULL COMMENT '叙利亚语',
	`syr-sy` VARCHAR(50) NULL DEFAULT NULL COMMENT '叙利亚语',
	`ta` VARCHAR(50) NULL DEFAULT NULL COMMENT '泰米尔语',
	`ta-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '泰米尔语',
	`te` VARCHAR(50) NULL DEFAULT NULL COMMENT '泰卢固语',
	`te-in` VARCHAR(50) NULL DEFAULT NULL COMMENT '泰卢固语',
	`th` VARCHAR(50) NULL DEFAULT NULL COMMENT '泰语',
	`th-th` VARCHAR(50) NULL DEFAULT NULL COMMENT '泰语',
	`tl` VARCHAR(50) NULL DEFAULT NULL COMMENT '塔加路语',
	`tl-ph` VARCHAR(50) NULL DEFAULT NULL COMMENT '塔加路语(菲律宾)',
	`tn` VARCHAR(50) NULL DEFAULT NULL COMMENT '茨瓦纳语',
	`tn-za` VARCHAR(50) NULL DEFAULT NULL COMMENT '茨瓦纳语',
	`tr` VARCHAR(50) NULL DEFAULT NULL COMMENT '土耳其语',
	`tr-tr` VARCHAR(50) NULL DEFAULT NULL COMMENT '土耳其语',
	`ts` VARCHAR(50) NULL DEFAULT NULL COMMENT '宗加语',
	`tt` VARCHAR(50) NULL DEFAULT NULL COMMENT '鞑靼语',
	`tt-ru` VARCHAR(50) NULL DEFAULT NULL COMMENT '鞑靼语',
	`uk` VARCHAR(50) NULL DEFAULT NULL COMMENT '乌克兰语',
	`uk-ua` VARCHAR(50) NULL DEFAULT NULL COMMENT '乌克兰语',
	`ur` VARCHAR(50) NULL DEFAULT NULL COMMENT '乌都语',
	`ur-pk` VARCHAR(50) NULL DEFAULT NULL COMMENT '乌都语',
	`uz` VARCHAR(50) NULL DEFAULT NULL COMMENT '乌兹别克语',
	`uz-uz` VARCHAR(50) NULL DEFAULT NULL COMMENT '乌兹别克语',
	`vi` VARCHAR(50) NULL DEFAULT NULL COMMENT '越南语',
	`vi-vn` VARCHAR(50) NULL DEFAULT NULL COMMENT '越南语',
	`xh` VARCHAR(50) NULL DEFAULT NULL COMMENT '班图语',
	`xh-za` VARCHAR(50) NULL DEFAULT NULL COMMENT '班图语',
	`zh` VARCHAR(50) NULL DEFAULT NULL COMMENT '中文',
	`zh-cn` VARCHAR(50) NULL DEFAULT NULL COMMENT '中文(简体)',
	`zh-hk` VARCHAR(50) NULL DEFAULT NULL COMMENT '中文(香港)',
	`zh-mo` VARCHAR(50) NULL DEFAULT NULL COMMENT '中文(澳门)',
	`zh-sg` VARCHAR(50) NULL DEFAULT NULL COMMENT '中文(新加坡)',
	`zh-tw` VARCHAR(50) NULL DEFAULT NULL COMMENT '中文(繁体)',
	`zu` VARCHAR(50) NULL DEFAULT NULL COMMENT '祖鲁语',
	`zu-za` VARCHAR(50) NULL DEFAULT NULL COMMENT '祖鲁语',
	PRIMARY KEY (`lid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
AUTO_INCREMENT=70
;