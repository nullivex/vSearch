-- phpMyAdmin SQL Dump
-- version 2.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 27, 2007 at 02:17 AM
-- Server version: 5.0.38
-- PHP Version: 5.2.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `talkxbox`
--

-- --------------------------------------------------------

--
-- Table structure for table `search`
--

CREATE TABLE `search` (
  `id` int(20) NOT NULL auto_increment,
  `pageid` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `created` int(20) NOT NULL,
  `url` varchar(300) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

--
-- Table structure for table `search_words`
--

CREATE TABLE `search_words` (
  `id` int(20) NOT NULL auto_increment,
  `sid` int(20) NOT NULL,
  `word` varchar(100) NOT NULL,
  `density` int(5) NOT NULL,
  `location` int(10) NOT NULL,
  `weight` int(5) NOT NULL default '1',
  `title` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
