-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 10, 2017 at 07:36 PM
-- Server version: 5.7.17-0ubuntu0.16.04.1
-- PHP Version: 7.0.12-1+deb.sury.org~xenial+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `books_shop`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_category` (IN `id_parent_category` INTEGER(9), IN `name_of_category` VARCHAR(45))  begin
DECLARE lvl int;
DECLARE r_key int;
start transaction;
select parent.row, parent.right_key INTO lvl, r_key FROM categories as parent where parent.category_id = id_parent_category;
        UPDATE categories SET left_key = left_key + 2, right_key = right_key + 2 WHERE left_key > r_key;
        UPDATE categories SET right_key = right_key + 2 WHERE right_key >= r_key AND left_key < r_key;
        INSERT INTO categories SET name_category = name_of_category, row = lvl + 1, left_key = r_key, right_key = r_key + 1;
commit;
end$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `change_order` (IN `id_moved_node` INT, IN `set_after` INT)  proc:BEGIN
-- Переменные для выбора родителя смещяемого -- 
DECLARE parent_id, parent_l_key, parent_r_key INT;
-- Переменные выбора соседа --
DECLARE after_key, after_l_key, after_row INT;
-- Переменная для выбора родителя узла к которому идёт смещение --
DECLARE parent_after_id INT;
-- Переменные для выбора перемещаемого узла --
DECLARE moved_row, moved_l_key, moved_r_key INT;
-- Вспомогательные переменные для расчёта смещения --
DECLARE skew_tree, skew_edit INT;
-- Узел не может перемещаться сам за себя --
IF id_moved_node = set_after THEN
	LEAVE proc;
END IF;

START TRANSACTION;
	-- Выбор перемещаемого узла --
    SELECT moved.row, moved.right_key, moved.left_key 
		INTO moved_row, moved_r_key, moved_l_key
		FROM categories AS moved
        WHERE moved.category_id = id_moved_node;
	-- Выбор родительского узла у перемещаемого узла --
	SELECT c.category_id, c.left_key, c.right_key
		INTO parent_id, parent_l_key, parent_r_key
		FROM categories AS c
        WHERE c.right_key > moved_r_key
        AND c.left_key < moved_l_key
        AND (moved_row - c.row) = 1;
	-- Выбор узла к которому идёт перемещение --
	SELECT category_after.right_key, category_after.left_key, category_after.row
		INTO after_key, after_l_key, after_row
		FROM categories AS category_after 
		WHERE category_after.category_id = set_after;
	-- У первого узла нет родителя --
    SET @cati = after_l_key;
    IF(after_l_key != 1 ) THEN
		-- Выбор родительского узла к которому идёт перемещение --
		SELECT after_parent.category_id
			INTO parent_after_id
			FROM categories AS after_parent
			WHERE after_parent.right_key > after_key
			AND after_parent.left_key < after_l_key
			AND (after_row - after_parent.row) = 1;
	ELSE
		-- Выбор родительского узла к которому идёт перемещение --
		SELECT after_parent.category_id
			INTO parent_after_id
			FROM categories AS after_parent
			WHERE after_parent.left_key = 1;
	END IF;
	-- Узел к которому перемещаем может быть либо родителем, либо иметь общего родителя --
	IF(set_after = parent_id) THEN
		SET after_key = after_l_key;
	ELSEIF(parent_after_id != parent_id OR moved_l_key = 1) THEN
		LEAVE proc;
    END IF;
	-- Определение смещения дерева -- 
    SET skew_tree = moved_r_key - moved_l_key + 1;
	-- Определяем куда сдигается узел --
	IF moved_r_key > after_key THEN
		-- Определение смещения перемещаемой ветки --
		SET skew_edit = after_key - moved_l_key + 1;
		-- Изменение дерева --
		UPDATE categories AS c 
			SET c.right_key = 
				IF(c.left_key >= moved_l_key, 
					c.right_key + skew_edit,
					IF(c.right_key < moved_l_key,
						c.right_key + skew_tree,
						c.right_key)),
				c.left_key = 
					IF(c.left_key >= moved_l_key,
						c.left_key + skew_edit,
						IF(c.left_key > after_key,
							c.left_key + skew_tree,
							c.left_key))
			WHERE c.right_key > after_key
			AND c.left_key < moved_r_key;
	ELSEIF moved_r_key < after_key THEN
		-- Опр�
$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `change_parent` (IN `id_moved_node` INT, IN `id_parent_node` INT)  proc:BEGIN
-- Переменные для выбора нового родителя -- 
DECLARE parent_row, parent_r_key, parent_l_key INT;
-- Переменные для выбора перемещаемого узла
DECLARE child_row, child_r_key, child_l_key INT;
-- Вспомогательные переменные для расчёта смещения --
DECLARE skew_tree, skew_row, skew_edit INT;
START TRANSACTION;
    -- Выбор родительского узла --
	SELECT parent.row, (parent.right_key - 1), parent.left_key
		INTO parent_row, parent_r_key, parent_l_key 
		FROM categories AS parent 
		WHERE parent.category_id = id_parent_node;
	-- Выбор нового дочернего узла, он же перемещаемый --
    SELECT child.row, child.right_key, child.left_key 
		INTO child_row, child_r_key, child_l_key
		FROM categories AS child
        WHERE child.category_id = id_moved_node;
	-- Узел не может перемещаться сам в себя или быть корневым --
	IF id_moved_node = id_parent_node OR child_l_key = 1 OR parent_l_key > child_l_key AND parent_r_key < child_r_key THEN
		LEAVE proc;
	END IF;
	-- Определение смещения дерева -- 
    SET skew_tree = child_r_key - child_l_key + 1;
    -- Определение смещения уровня у перемещаемого узла --
    SET skew_row = parent_row - child_row + 1;
	-- Определяем куда сдигается узел --
	IF child_r_key > parent_r_key THEN
		-- Определение смещения перемещаемой ветки --
		SET skew_edit = parent_r_key - child_l_key + 1;
		-- Изменение дерева --
		UPDATE categories AS c 
			SET c.right_key = 
				IF(c.left_key >= child_l_key, 
					c.right_key + skew_edit,
					IF(c.right_key < child_l_key,
						c.right_key + skew_tree,
						c.right_key)),
				c.row = 
					IF(c.left_key >= child_l_key, 
						c.row + skew_row, 
						c.row),
				c.left_key = 
					IF(c.left_key >= child_l_key,
						c.left_key + skew_edit,
						IF(c.left_key > parent_r_key,
							c.left_key + skew_tree,
							c.left_key))
			WHERE c.right_key > parent_r_key
			AND c.left_key < child_r_key;
	ELSEIF child_r_key < parent_r_key THEN
		-- Определение смешения перемещаемой ветки --
		SET skew_edit = parent_r_key - child_l_key + 1 - skew_tree;
        -- Изменение ключей дерева дерева -- 
        SET @r = skew_edit;
		UPDATE categories AS c 
			SET c.left_key = 
					IF(c.right_key <= child_r_key,
						c.left_key + skew_edit,
						IF(c.left_key > child_r_key,
							c.left_key - skew_tree,
							c.left_key)),
				c.row = 
					IF(c.right_key <=$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_category` (IN `id_delete_category` INT)  BEGIN
declare l_key int;
declare r_key int;
start transaction;
SELECT c.left_key, c.right_key INTO l_key, r_key FROM categories as c WHERE category_id = id_delete_category;
    DELETE FROM categories WHERE categories.left_key >= l_key AND categories.right_key <= r_key;
    UPDATE categories AS c SET c.right_key = c.right_key - (r_key - l_key + 1) WHERE c.right_key > r_key AND c.left_key < l_key;
    UPDATE categories AS c SET c.left_key = c.left_key - (r_key - l_key + 1), c.right_key = c.right_key - (r_key - l_key + 1) WHERE c.left_key > r_key;
    
commit;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(9) UNSIGNED NOT NULL,
  `book_name` tinytext NOT NULL,
  `authors` tinytext NOT NULL,
  `price` varchar(45) NOT NULL,
  `preview_img` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `book_name`, `authors`, `price`, `preview_img`) VALUES
(49, 'Книга 2', 'я', '20000', 'c8/30/3dcc1709bb019ef04da9f4b411d8af1e.jpg'),
(50, 'Книга 3', 'опять я ', '2', '5d/63/84e2d3e5e88a2bf94ecf801be37a09d8.jpeg'),
(51, 'Книга 4', 'я', '12000', 'b1/10/82801f288a22956a261680c68733ca0a.jpg'),
(52, 'Бука', 'СОня', '25000', '5c/de/edcba2e98fedc0359ffb4da7f7e2f5d8.jpeg'),
(54, 'Попытка 2', '2', '0', 'a6/06/e52a12e50329fca81d1ea9e12c283428.jpeg'),
(55, 'aa', 'ww', '20000', '1d/cb/3c3f58cf4e70757f8e6e0261d4b5e6cb.jpeg'),
(56, 'Новая', 'awd', '2000', '02/e2/ebe9ed66e174018193eac0cabff4b1b6.jpeg');

--
-- Triggers `books`
--
DELIMITER $$
CREATE TRIGGER `books_AFTER_INSERT` AFTER INSERT ON `books` FOR EACH ROW BEGIN
	INSERT INTO books_log SET 
    user = CURRENT_USER(),
    action = 'insert',
    time = NOW(),
    books_book_id = NEW.book_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `books_BEFORE_DELETE` BEFORE DELETE ON `books` FOR EACH ROW BEGIN
INSERT INTO books_log SET 
    user = CURRENT_USER(),
    action = 'delete',
    time = NOW(),
    books_book_id = OLD.book_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `books_BEFORE_UPDATE` BEFORE UPDATE ON `books` FOR EACH ROW BEGIN
INSERT INTO books_log SET 
    user = CURRENT_USER(),
    action = 'update',
    time = NOW(),
    books_book_id = OLD.book_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `books_log`
--

CREATE TABLE `books_log` (
  `books_log_id` int(9) UNSIGNED NOT NULL,
  `user` varchar(45) NOT NULL,
  `action` char(6) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `books_book_id` int(9) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Stored logs ';

--
-- Dumping data for table `books_log`
--

INSERT INTO `books_log` (`books_log_id`, `user`, `action`, `time`, `books_book_id`) VALUES
(1, 'root@localhost', 'insert', '2016-12-05 17:47:29', 0),
(2, 'root@localhost', 'insert', '2016-12-06 00:17:53', 0),
(3, 'root@localhost', 'update', '2016-12-06 00:18:28', 0),
(4, 'root@localhost', 'delete', '2016-12-06 00:18:48', 0),
(5, 'root@localhost', 'insert', '2016-12-19 12:57:22', 12),
(6, 'root@localhost', 'delete', '2016-12-19 12:58:07', 12),
(7, 'root@localhost', 'insert', '2016-12-29 17:54:50', 12),
(8, 'root@localhost', 'insert', '2016-12-29 17:56:14', 13),
(9, 'root@localhost', 'insert', '2016-12-29 18:23:35', 14),
(10, 'root@localhost', 'insert', '2016-12-29 18:25:21', 15),
(11, 'root@localhost', 'insert', '2016-12-29 18:26:02', 16),
(12, 'root@localhost', 'insert', '2016-12-29 18:29:31', 17),
(13, 'root@localhost', 'insert', '2016-12-31 01:08:33', 12),
(14, 'root@localhost', 'insert', '2016-12-31 01:09:13', 13),
(15, 'root@localhost', 'insert', '2016-12-31 01:10:06', 14),
(16, 'root@localhost', 'delete', '2016-12-31 01:11:14', 13),
(17, 'root@localhost', 'delete', '2016-12-31 01:11:14', 14),
(18, 'root@localhost', 'insert', '2016-12-31 01:13:23', 15),
(19, 'root@localhost', 'insert', '2016-12-31 01:13:28', 16),
(20, 'root@localhost', 'insert', '2016-12-31 01:13:34', 17),
(21, 'root@localhost', 'insert', '2016-12-31 01:13:51', 18),
(22, 'root@localhost', 'insert', '2016-12-31 01:13:56', 19),
(23, 'root@localhost', 'insert', '2016-12-31 01:14:19', 20),
(24, 'root@localhost', 'delete', '2016-12-31 01:14:54', 15),
(25, 'root@localhost', 'delete', '2016-12-31 01:14:54', 16),
(26, 'root@localhost', 'delete', '2016-12-31 01:14:54', 17),
(27, 'root@localhost', 'delete', '2016-12-31 01:14:54', 18),
(28, 'root@localhost', 'delete', '2016-12-31 01:14:54', 19),
(29, 'root@localhost', 'delete', '2016-12-31 01:14:54', 20),
(30, 'root@localhost', 'insert', '2016-12-31 01:23:38', 21),
(31, 'root@localhost', 'insert', '2016-12-31 01:31:33', 22),
(32, 'root@localhost', 'insert', '2016-12-31 01:32:33', 23),
(33, 'root@localhost', 'delete', '2016-12-31 01:32:58', 22),
(34, 'root@localhost', 'delete', '2016-12-31 01:32:58', 23),
(35, 'root@localhost', 'insert', '2016-12-31 01:38:44', 24),
(36, 'root@localhost', 'delete', '2016-12-31 01:38:54', 21),
(37, 'root@localhost', 'delete', '2016-12-31 05:48:27', 24),
(38, 'root@localhost', 'delete', '2016-12-31 05:53:12', 24),
(39, 'root@localhost', 'insert', '2016-12-31 05:57:31', 25),
(40, 'root@localhost', 'delete', '2016-12-31 05:57:51', 25),
(41, 'root@localhost', 'insert', '2016-12-31 05:58:10', 26),
(42, 'root@localhost', 'delete', '2016-12-31 05:58:32', 26),
(43, 'root@localhost', 'delete', '2016-12-31 05:59:09', 26),
(44, 'root@localhost', 'insert', '2017-01-02 03:30:25', 12),
(45, 'root@localhost', 'insert', '2017-01-02 17:04:20', 13),
(46, 'root@localhost', 'delete', '2017-01-02 17:40:03', 12),
(47, 'root@localhost', 'update', '2017-01-02 17:48:17', 13),
(48, 'root@localhost', 'update', '2017-01-02 17:51:08', 13),
(49, 'root@localhost', 'update', '2017-01-02 17:51:26', 13),
(50, 'root@localhost', 'insert', '2017-01-02 17:52:17', 14),
(51, 'root@localhost', 'update', '2017-01-02 17:52:37', 14),
(52, 'root@localhost', 'update', '2017-01-02 17:53:52', 14),
(53, 'root@localhost', 'update', '2017-01-02 17:55:25', 14),
(54, 'root@localhost', 'update', '2017-01-02 17:55:40', 14),
(55, 'root@localhost', 'delete', '2017-01-02 17:56:30', 13),
(56, 'root@localhost', 'delete', '2017-01-02 17:56:30', 14),
(57, 'root@localhost', 'insert', '2017-01-02 17:56:38', 15),
(58, 'root@localhost', 'update', '2017-01-02 17:56:55', 15),
(59, 'root@localhost', 'update', '2017-01-02 18:00:30', 15),
(60, 'root@localhost', 'update', '2017-01-02 18:08:05', 15),
(61, 'root@localhost', 'update', '2017-01-20 18:36:01', 2),
(62, 'root@localhost', 'update', '2017-01-20 18:39:43', 2),
(63, 'root@localhost', 'update', '2017-01-20 18:40:10', 2),
(64, 'root@localhost', 'update', '2017-01-20 18:52:11', 2),
(65, 'root@localhost', 'insert', '2017-01-21 21:45:54', 16),
(66, 'root@localhost', 'delete', '2017-01-21 21:46:50', 16),
(67, 'root@localhost', 'insert', '2017-01-21 21:49:02', 17),
(68, 'root@localhost', 'delete', '2017-01-21 21:49:53', 17),
(69, 'root@localhost', 'insert', '2017-01-21 22:33:40', 18),
(70, 'root@localhost', 'delete', '2017-01-21 22:34:27', 18),
(71, 'root@localhost', 'insert', '2017-01-21 22:34:46', 19),
(72, 'root@localhost', 'update', '2017-01-21 22:35:02', 19),
(73, 'root@localhost', 'delete', '2017-01-21 22:35:32', 19),
(74, 'root@localhost', 'insert', '2017-01-22 15:48:01', 16),
(75, 'root@localhost', 'delete', '2017-01-22 15:49:33', 16),
(76, 'root@localhost', 'delete', '2017-01-22 15:52:09', 16),
(77, 'root@localhost', 'insert', '2017-01-25 17:32:33', 16),
(78, 'root@localhost', 'insert', '2017-01-25 17:34:00', 17),
(79, 'root@localhost', 'insert', '2017-01-25 17:34:26', 18),
(80, 'root@localhost', 'insert', '2017-01-25 17:39:08', 19),
(81, 'root@localhost', 'insert', '2017-01-25 17:39:25', 20),
(82, 'root@localhost', 'insert', '2017-01-25 17:40:20', 21),
(83, 'root@localhost', 'insert', '2017-01-25 17:40:48', 22),
(84, 'root@localhost', 'insert', '2017-01-25 17:41:29', 23),
(85, 'root@localhost', 'insert', '2017-01-25 17:42:07', 24),
(86, 'root@localhost', 'insert', '2017-01-25 17:51:07', 25),
(87, 'root@localhost', 'insert', '2017-01-25 17:51:30', 26),
(88, 'root@localhost', 'insert', '2017-01-25 17:51:51', 27),
(89, 'root@localhost', 'insert', '2017-01-25 17:52:59', 28),
(90, 'root@localhost', 'insert', '2017-01-25 17:53:23', 29),
(91, 'root@localhost', 'insert', '2017-01-25 17:53:38', 30),
(92, 'root@localhost', 'insert', '2017-01-25 17:54:35', 31),
(93, 'root@localhost', 'insert', '2017-01-25 17:54:53', 32),
(94, 'root@localhost', 'insert', '2017-01-25 17:55:02', 33),
(95, 'root@localhost', 'insert', '2017-01-25 17:55:16', 34),
(96, 'root@localhost', 'insert', '2017-01-25 17:55:28', 35),
(97, 'root@localhost', 'insert', '2017-01-25 17:55:54', 36),
(98, 'root@localhost', 'insert', '2017-01-25 17:57:02', 37),
(99, 'root@localhost', 'insert', '2017-01-25 17:57:23', 38),
(100, 'root@localhost', 'insert', '2017-01-25 17:58:31', 39),
(101, 'root@localhost', 'insert', '2017-01-25 18:00:23', 40),
(102, 'root@localhost', 'insert', '2017-01-25 18:31:17', 41),
(103, 'root@localhost', 'insert', '2017-01-25 18:32:06', 42),
(104, 'root@localhost', 'insert', '2017-01-25 18:33:29', 43),
(105, 'root@localhost', 'insert', '2017-01-25 18:40:50', 44),
(106, 'root@localhost', 'insert', '2017-01-25 18:41:45', 45),
(107, 'root@localhost', 'insert', '2017-01-25 18:42:11', 46),
(108, 'root@localhost', 'insert', '2017-01-25 18:42:34', 47),
(109, 'root@localhost', 'insert', '2017-01-25 18:42:51', 48),
(110, 'root@localhost', 'insert', '2017-01-25 18:44:21', 49),
(111, 'root@localhost', 'insert', '2017-01-25 18:44:30', 50),
(112, 'root@localhost', 'insert', '2017-01-25 18:44:50', 51),
(113, 'root@localhost', 'insert', '2017-01-25 18:44:57', 52),
(114, 'root@localhost', 'insert', '2017-01-25 18:49:37', 53),
(115, 'root@localhost', 'insert', '2017-01-25 18:49:54', 54),
(116, 'root@localhost', 'insert', '2017-01-25 18:51:03', 55),
(117, 'root@localhost', 'insert', '2017-01-25 18:52:23', 56),
(118, 'root@localhost', 'insert', '2017-01-25 18:53:07', 57),
(119, 'root@localhost', 'insert', '2017-01-25 18:53:56', 58),
(120, 'root@localhost', 'insert', '2017-01-25 18:54:23', 59),
(121, 'root@localhost', 'insert', '2017-01-25 18:55:18', 60),
(122, 'root@localhost', 'insert', '2017-01-25 18:55:36', 61),
(123, 'root@localhost', 'insert', '2017-01-25 18:56:32', 62),
(124, 'root@localhost', 'insert', '2017-01-25 18:57:31', 63),
(125, 'root@localhost', 'insert', '2017-01-26 14:44:04', 16),
(126, 'root@localhost', 'insert', '2017-01-26 14:44:40', 17),
(127, 'root@localhost', 'insert', '2017-01-26 14:45:04', 18),
(128, 'root@localhost', 'insert', '2017-01-26 14:46:49', 19),
(129, 'root@localhost', 'insert', '2017-01-26 14:50:04', 20),
(130, 'root@localhost', 'insert', '2017-01-26 14:52:55', 21),
(131, 'root@localhost', 'insert', '2017-01-26 14:53:19', 22),
(132, 'root@localhost', 'insert', '2017-01-26 14:55:15', 23),
(133, 'root@localhost', 'insert', '2017-01-26 14:56:00', 24),
(134, 'root@localhost', 'insert', '2017-01-26 14:56:46', 25),
(135, 'root@localhost', 'insert', '2017-01-26 14:59:14', 26),
(136, 'root@localhost', 'insert', '2017-01-26 14:59:25', 27),
(137, 'root@localhost', 'insert', '2017-01-26 14:59:40', 28),
(138, 'root@localhost', 'insert', '2017-01-26 15:00:49', 29),
(139, 'root@localhost', 'insert', '2017-01-26 15:01:13', 30),
(140, 'root@localhost', 'insert', '2017-01-26 15:01:28', 31),
(141, 'root@localhost', 'insert', '2017-01-26 15:01:49', 32),
(142, 'root@localhost', 'insert', '2017-01-26 15:04:30', 33),
(143, 'root@localhost', 'insert', '2017-01-26 15:04:54', 34),
(144, 'root@localhost', 'insert', '2017-01-26 15:05:10', 35),
(145, 'root@localhost', 'insert', '2017-01-26 15:06:28', 36),
(146, 'root@localhost', 'insert', '2017-01-26 15:09:16', 37),
(147, 'root@localhost', 'insert', '2017-01-26 15:09:39', 38),
(148, 'root@localhost', 'insert', '2017-01-26 15:09:57', 39),
(149, 'root@localhost', 'insert', '2017-01-26 15:10:21', 40),
(150, 'root@localhost', 'delete', '2017-01-26 15:11:27', 25),
(151, 'root@localhost', 'delete', '2017-01-26 15:11:27', 29),
(152, 'root@localhost', 'delete', '2017-01-26 15:11:27', 36),
(153, 'root@localhost', 'insert', '2017-01-26 15:19:30', 41),
(154, 'root@localhost', 'insert', '2017-01-26 15:20:14', 42),
(155, 'root@localhost', 'insert', '2017-01-26 15:22:46', 43),
(156, 'root@localhost', 'delete', '2017-01-26 15:25:49', 41),
(157, 'root@localhost', 'delete', '2017-01-26 15:25:49', 42),
(158, 'root@localhost', 'delete', '2017-01-26 15:25:49', 43),
(159, 'root@localhost', 'insert', '2017-01-26 15:26:39', 44),
(160, 'root@localhost', 'delete', '2017-01-26 15:50:55', 44),
(161, 'root@localhost', 'insert', '2017-01-26 15:52:38', 45),
(162, 'root@localhost', 'insert', '2017-01-26 15:53:05', 46),
(163, 'root@localhost', 'insert', '2017-01-26 15:53:53', 47),
(164, 'root@localhost', 'insert', '2017-01-26 15:54:19', 48),
(165, 'root@localhost', 'insert', '2017-01-26 15:55:20', 49),
(166, 'root@localhost', 'insert', '2017-01-26 16:02:47', 50),
(167, 'root@localhost', 'update', '2017-01-26 16:04:46', 50),
(168, 'root@localhost', 'delete', '2017-01-26 16:05:23', 2),
(169, 'root@localhost', 'delete', '2017-01-26 16:05:23', 3),
(170, 'root@localhost', 'delete', '2017-01-26 16:05:23', 4),
(171, 'root@localhost', 'delete', '2017-01-26 16:05:24', 5),
(172, 'root@localhost', 'delete', '2017-01-26 16:05:24', 7),
(173, 'root@localhost', 'delete', '2017-01-26 16:05:24', 8),
(174, 'root@localhost', 'delete', '2017-01-26 16:05:24', 9),
(175, 'root@localhost', 'delete', '2017-01-26 16:05:24', 10),
(176, 'root@localhost', 'delete', '2017-01-26 16:05:24', 11),
(177, 'root@localhost', 'delete', '2017-01-26 16:05:24', 15),
(178, 'root@localhost', 'insert', '2017-01-28 03:23:39', 51),
(179, 'root@localhost', 'insert', '2017-01-28 03:27:52', 52),
(180, 'root@localhost', 'insert', '2017-01-28 03:36:35', 53),
(181, 'root@localhost', 'insert', '2017-01-30 16:22:34', 51),
(182, 'root@localhost', 'update', '2017-01-30 17:05:13', 51),
(183, 'root@localhost', 'insert', '2017-01-31 10:18:23', 52),
(184, 'root@localhost', 'insert', '2017-02-01 14:24:30', 53),
(185, 'root@localhost', 'insert', '2017-02-01 14:24:49', 54),
(186, 'root@localhost', 'insert', '2017-02-02 17:52:05', 55),
(187, 'root@localhost', 'insert', '2017-02-02 18:59:12', 56),
(188, 'root@localhost', 'update', '2017-02-03 00:21:54', 54),
(189, 'root@localhost', 'update', '2017-02-03 00:22:06', 54),
(190, 'root@localhost', 'update', '2017-02-03 00:22:27', 54),
(191, 'root@localhost', 'update', '2017-02-03 00:24:16', 54),
(192, 'root@localhost', 'update', '2017-02-03 00:25:06', 54),
(193, 'root@localhost', 'update', '2017-02-03 00:27:14', 54),
(194, 'root@localhost', 'update', '2017-02-03 00:30:17', 54),
(195, 'root@localhost', 'update', '2017-02-03 00:31:21', 54),
(196, 'root@localhost', 'update', '2017-02-03 00:31:57', 54),
(197, 'root@localhost', 'update', '2017-02-03 00:32:22', 54),
(198, 'root@localhost', 'update', '2017-02-03 00:32:44', 54),
(199, 'root@localhost', 'update', '2017-02-03 00:34:31', 54),
(200, 'root@localhost', 'update', '2017-02-03 00:35:22', 54),
(201, 'root@localhost', 'update', '2017-02-03 00:38:04', 54),
(202, 'root@localhost', 'update', '2017-02-03 00:39:08', 54),
(203, 'root@localhost', 'update', '2017-02-03 00:46:56', 54),
(204, 'root@localhost', 'update', '2017-02-03 00:47:06', 54),
(205, 'root@localhost', 'update', '2017-02-03 01:01:15', 50),
(206, 'root@localhost', 'update', '2017-02-03 01:01:29', 50),
(207, 'root@localhost', 'update', '2017-02-03 01:01:42', 50);

-- --------------------------------------------------------

--
-- Table structure for table `books_properties`
--

CREATE TABLE `books_properties` (
  `books_book_id` int(9) UNSIGNED NOT NULL,
  `description` text,
  `date_of_release` year(4) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `printing` int(9) DEFAULT NULL,
  `books_img` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `books_properties`
--

INSERT INTO `books_properties` (`books_book_id`, `description`, `date_of_release`, `language`, `printing`, `books_img`) VALUES
(49, 'обновил', 2017, 'енг', 12124, '0:92/28/593fbe04688be1299d1a20796cc91215.jpg;1:db/2e/53a1d6953fb6e97ec1065112cf9837d0.jpg'),
(50, 'обновил', 2017, 'енг', 12124, '0:9b/b2/f8ecdbd07ac4bc7e0d8bc0410144d502.jpg'),
(51, 'обновил', 2017, 'енг', 12124, '0:e5/68/463bba8d16c0c4e0a73f4a9c8c00f59f.jpeg;1:c3/c3/f8c0e29ab64c0996d34cf67fb7c65db0.jpeg'),
(52, 'обновил', 2017, 'енг', 12124, '0:2e/79/abc4cccc1efbf266adc93ba1a3433607.jpeg;1:c3/ff/f8cc618837f53eaf4df4bfad76bd4986.jpeg;2:cb/60/27c92e91184e8ca9ea9cb5f443bae3b1.jpeg'),
(54, 'обновил 2 ', 2017, 'обнова', 5, '0:f2/7b/22c82fd8647f4926858c8a8f8800fbdb.jpeg;1:3c/45/b10b93531d3f51d73f76fcf064931923.jpeg'),
(55, 'обновил', 2017, 'енг', 12124, '0:54/8f/e76971347c716b783d04802bc02586c9.jpeg;1:2c/3e/c01fd09763c1a76a05823a22e7fc4456.jpeg'),
(56, 'обновил', 2017, 'енг', 12124, '0:82/3f/04d71b0d5cd122387db83649bc00e376.jpeg;1:07/b8/4a016ea565c8c5a935b5394d4840a4de.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(9) UNSIGNED NOT NULL,
  `name_category` varchar(70) NOT NULL,
  `row` int(3) UNSIGNED NOT NULL,
  `left_key` int(9) UNSIGNED NOT NULL,
  `right_key` int(9) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table contain nested sets architecture of categories hierarchy';

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name_category`, `row`, `left_key`, `right_key`) VALUES
(1, 'Все', 0, 1, 16),
(2, 'Художественная литература', 1, 10, 15),
(3, 'Классическая и современная проза', 2, 11, 12),
(4, 'Кинороманы', 2, 13, 14),
(5, 'Учебная литература', 1, 2, 7),
(6, 'Технические науки', 2, 3, 4),
(8, 'Русская литература', 1, 8, 9),
(20, 'Естественные науки', 2, 5, 6);

-- --------------------------------------------------------

--
-- Table structure for table `categories_has_books`
--

CREATE TABLE `categories_has_books` (
  `categories_category_id` int(9) UNSIGNED NOT NULL,
  `books_book_id` int(9) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `categories_has_books`
--

INSERT INTO `categories_has_books` (`categories_category_id`, `books_book_id`) VALUES
(2, 49),
(8, 50),
(4, 51),
(5, 52),
(1, 54),
(6, 54),
(1, 55),
(2, 55),
(20, 55),
(1, 56),
(2, 56),
(3, 56),
(5, 56),
(8, 56);

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `feedback_id` int(9) UNSIGNED NOT NULL,
  `user_id` int(9) UNSIGNED NOT NULL,
  `books_id` int(9) UNSIGNED NOT NULL,
  `comment` text,
  `value` int(1) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(9) UNSIGNED NOT NULL,
  `email` varchar(254) NOT NULL,
  `password` char(60) NOT NULL,
  `role` enum('ADMIN','USER') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`) VALUES
(9, 'admin', '$2y$10$ProH7jipE2tXUQF0FNpcR.R0SJOai.wIByMZAdyCK9YJw.zGZXayK', 'ADMIN'),
(10, 'user1', '$2y$10$/oOl9xqsXRVRJ0qWRqfM/utxsVgOqv7pMTSPOqxeCsbCWcH89cv4m', 'USER'),
(11, 'user2', '$2y$10$KH8O.0lq3rCeFe/uMOoJkOSik/Da30Q9VL8EfKrSFY5iLpzgQZqEq', 'USER');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `users_AFTER_INSERT` AFTER INSERT ON `users` FOR EACH ROW BEGIN
	INSERT INTO users_log SET
    user = CURRENT_USER(),
    action = 'insert',
    time = NOW(),
    users_user_id = NEW.user_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `users_BEFORE_DELETE` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
	INSERT INTO users_log SET 
    user = CURRENT_USER(),
    action = 'delete',
    time = NOW(),
    users_user_id = OLD.user_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `users_BEFORE_UPDATE` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
	INSERT INTO users_log SET 
    user = CURRENT_USER(),
    action = 'update',
    time = NOW(),
    users_user_id = OLD.user_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users_log`
--

CREATE TABLE `users_log` (
  `user_log_id` int(9) UNSIGNED NOT NULL,
  `user` varchar(45) NOT NULL,
  `action` char(6) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `users_user_id` int(9) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Stroed users log';

--
-- Dumping data for table `users_log`
--

INSERT INTO `users_log` (`user_log_id`, `user`, `action`, `time`, `users_user_id`) VALUES
(1, 'root@localhost', 'insert', '2016-12-06 00:34:24', 0),
(2, 'root@localhost', 'update', '2016-12-06 00:34:40', 0),
(3, 'root@localhost', 'delete', '2016-12-06 00:34:48', 0),
(4, 'root@localhost', 'insert', '2016-12-06 05:18:45', 0),
(5, 'root@localhost', 'insert', '2016-12-19 13:03:44', 4),
(6, 'root@localhost', 'insert', '2016-12-19 13:22:42', 5),
(7, 'root@localhost', 'delete', '2017-01-06 22:23:15', 5),
(8, 'root@localhost', 'delete', '2017-01-06 22:23:15', 2),
(9, 'root@localhost', 'delete', '2017-01-06 22:23:15', 4),
(10, 'root@localhost', 'insert', '2017-01-06 22:27:29', 6),
(11, 'root@localhost', 'insert', '2017-01-14 11:12:38', 7),
(12, 'root@localhost', 'insert', '2017-01-14 11:13:51', 8),
(13, 'root@localhost', 'delete', '2017-01-16 13:35:42', 6),
(14, 'root@localhost', 'delete', '2017-01-16 13:35:42', 7),
(15, 'root@localhost', 'delete', '2017-01-16 13:35:42', 8),
(16, 'root@localhost', 'insert', '2017-01-16 13:36:42', 9),
(17, 'root@localhost', 'insert', '2017-01-16 13:38:58', 10),
(18, 'root@localhost', 'insert', '2017-01-16 13:39:15', 11);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `books_log`
--
ALTER TABLE `books_log`
  ADD PRIMARY KEY (`books_log_id`);

--
-- Indexes for table `books_properties`
--
ALTER TABLE `books_properties`
  ADD PRIMARY KEY (`books_book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `categories_has_books`
--
ALTER TABLE `categories_has_books`
  ADD PRIMARY KEY (`categories_category_id`,`books_book_id`),
  ADD KEY `fk_categories_has_books_books1_idx` (`books_book_id`),
  ADD KEY `fk_categories_has_books_categories1_idx` (`categories_category_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `fk_feedbacks_Users1_idx` (`user_id`),
  ADD KEY `fk_feedbacks_books1_idx` (`books_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`);

--
-- Indexes for table `users_log`
--
ALTER TABLE `users_log`
  ADD PRIMARY KEY (`user_log_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;
--
-- AUTO_INCREMENT for table `books_log`
--
ALTER TABLE `books_log`
  MODIFY `books_log_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;
--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `feedback_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `users_log`
--
ALTER TABLE `users_log`
  MODIFY `user_log_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `books_properties`
--
ALTER TABLE `books_properties`
  ADD CONSTRAINT `fk_books_properties_books1` FOREIGN KEY (`books_book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `categories_has_books`
--
ALTER TABLE `categories_has_books`
  ADD CONSTRAINT `fk_categories_has_books_books1` FOREIGN KEY (`books_book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_categories_has_books_categories1` FOREIGN KEY (`categories_category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `fk_Feedbacks_Users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Feedbacks_books1` FOREIGN KEY (`books_id`) REFERENCES `books` (`book_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
