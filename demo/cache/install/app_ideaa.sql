-- --------------------------------------------------------
-- 主机:                           127.0.0.1
-- 服务器版本:                        5.0.22-community-nt-log - MySQL Community Edition (GPL)
-- 服务器操作系统:                      Win32
-- HeidiSQL 版本:                  8.1.0.4658
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 导出 blog 的数据库结构
DROP DATABASE IF EXISTS `blog`;
CREATE DATABASE IF NOT EXISTS `blog` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `blog`;


-- 导出  表 blog.back_acl_menu 结构
DROP TABLE IF EXISTS `back_acl_menu`;
CREATE TABLE IF NOT EXISTS `back_acl_menu` (
  `id` int(10) NOT NULL auto_increment,
  `pid` int(11) default '0',
  `type` tinyint(4) default '1' COMMENT '0系统,1用户',
  `name` varchar(128) collate utf8_unicode_ci default '',
  `link` varchar(64) collate utf8_unicode_ci default '',
  `status` tinyint(4) default '-1' COMMENT '状态-1:未激活',
  `display` tinyint(4) default '1' COMMENT '1:显示,0:不显示',
  `order` int(11) default '0' COMMENT '排序',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 数据导出被取消选择。


-- 导出  表 blog.back_acl_role 结构
DROP TABLE IF EXISTS `back_acl_role`;
CREATE TABLE IF NOT EXISTS `back_acl_role` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '' COMMENT '角色名称',
  `behavior` text COMMENT '允许的行为',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色管理';

-- 数据导出被取消选择。


-- 导出  表 blog.back_admin 结构
DROP TABLE IF EXISTS `back_admin`;
CREATE TABLE IF NOT EXISTS `back_admin` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `t` int(11) NOT NULL default '1' COMMENT '状态 1:正常',
  `rid` int(11) NOT NULL default '1' COMMENT '角色',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 数据导出被取消选择。


-- 导出  表 blog.back_securitycard 结构
DROP TABLE IF EXISTS `back_securitycard`;
CREATE TABLE IF NOT EXISTS `back_securitycard` (
  `id` int(11) NOT NULL auto_increment,
  `card_data` text character set utf8 collate utf8_unicode_ci NOT NULL,
  `bind_user` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
  `ext_time` int(11) unsigned NOT NULL default '0' COMMENT '0,正常',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 数据导出被取消选择。


-- 导出  表 blog.front_article 结构
DROP TABLE IF EXISTS `front_article`;
CREATE TABLE IF NOT EXISTS `front_article` (
  `id` int(11) NOT NULL auto_increment,
  `cid` tinyint(4) NOT NULL default '0' COMMENT '分类ID',
  `is_top` tinyint(4) NOT NULL default '0',
  `title` varchar(255) collate utf8_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL default '1',
  `author` varchar(128) collate utf8_unicode_ci NOT NULL default '',
  `intro` varchar(255) collate utf8_unicode_ci default '' COMMENT '简介',
  `content` text collate utf8_unicode_ci NOT NULL,
  `ct` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 数据导出被取消选择。


-- 导出  表 blog.front_article_tags 结构
DROP TABLE IF EXISTS `front_article_tags`;
CREATE TABLE IF NOT EXISTS `front_article_tags` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tid` int(10) unsigned NOT NULL default '0',
  `aid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `aid` (`aid`),
  KEY `tid` (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 数据导出被取消选择。


-- 导出  表 blog.front_reply 结构
DROP TABLE IF EXISTS `front_reply`;
CREATE TABLE IF NOT EXISTS `front_reply` (
  `id` int(11) NOT NULL auto_increment,
  `tid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `content` text character set utf8 collate utf8_unicode_ci NOT NULL,
  `rt` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 数据导出被取消选择。


-- 导出  表 blog.front_tags 结构
DROP TABLE IF EXISTS `front_tags`;
CREATE TABLE IF NOT EXISTS `front_tags` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 数据导出被取消选择。
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
