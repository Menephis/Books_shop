-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 17, 2017 at 11:18 AM
-- Server version: 5.7.16-0ubuntu0.16.04.1
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
-- Переменные для выбора родителя -- 
DECLARE parent_id, parent_l_key, parent_r_key INT;
-- Переменные выбора соседа --
DECLARE after_key, after_l_key INT;
-- Переменные для выбора перемещаемого узла
DECLARE moved_row, moved_l_key, moved_r_key INT;
-- Вспомогательные переменные для расчёта смещения --
DECLARE skew_tree, skew_edit INT;
-- Узел не может перемещаться сам за себя --
IF id_moved_node = set_after THEN
	LEAVE proc;
END IF;
START TRANSACTION;
    -- Выбор узла к которому идёт перемещение --
	SELECT category_after.right_key, category_after.left_key
		INTO after_key, after_l_key 
		FROM categories AS category_after 
		WHERE category_after.category_id = set_after;
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
	-- Узел к которому перемещаем может быть либо родителем, либо соседним --
	IF(set_after = parent_id) THEN
		SET after_key = after_l_key;
	ELSEIF(after_l_key < parent_l_key OR after_key > parent_r_key) THEN
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
		-- Определение смешения перемещаемой ветки --
		SET skew_edit = after_key - moved_l_key + 1 - skew_tree;
        -- Изменение ключей дерева �
$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `change_parent` (IN `id_moved_node` INT, IN `id_parent_node` INT)  proc:BEGIN
-- Переменные для выбора нового родителя -- 
DECLARE parent_row, parent_r_key, parent_l_key INT;
-- Переменные для выбора перемещаемого узла
DECLARE child_row, child_r_key, child_l_key INT;
-- Вспомогательные переменные для расчёта смещения --
DECLARE skew_tree, skew_row, skew_edit INT;
-- Узел не может перемещаться сам в себя --
IF id_moved_node = id_parent_node THEN
	LEAVE proc;
END IF;
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
					IF(c.right_key <= child_r_key, 
$$

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
  `description` text,
  `price` varchar(45) NOT NULL,
  `preview_img` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `book_name`, `description`, `price`, `preview_img`) VALUES
(2, 'Мастер и Маргарита', '"Мастер и Маргарита" М.А.Булгакова - самое удивительное и загадочное произведение XX века. Опубликованный в середине 1960-х, этот роман поразил читателей необычностью замысла, красочностью и фантастичностью действия, объединяющего героев разных эпох и культур. Автор создал "роман в романе", где сплетены воедино религиозно-историческая мистерия, восходящая к легенде о распятом Христе, московская "буффонада" и сверхъестественные сцены с персонажами, воплощающими некую темную силу, которая однако "вечно хочет зла и вечно совершает благо". \n\n"Есть в этой книге какая-то безрасчетность, какая-то предсмертная ослепительность большого таланта..." - писал Константин Симонов в своем предисловии к первой публикации романа, открывшей всему миру большого художника, подлинного Мастера слова.', '49000', ''),
(3, 'Братья Карамазовы', 'Последний, самый объемный и один из наиболее известных романов Ф.М.Достоевского обращает читателя к вневременным нравственно-философским вопросам о грехе, воздаянии, сострадании и милосердии. Книга, которую сам писатель определил как "роман о богохульстве и опровержении его", явилась попыткой "решить вопрос о человеке", "разгадать тайну" человека, что, по Достоевскому, означало "решить вопрос о Боге". Сквозь призму истории провинциальной семьи Карамазовых автор повествует об извечной борьбе Божественного и дьявольского в человеческой душе. Один из самых глубоких в мировой литературе опытов отражения христианского сознания, БРАТЬЯ КАРАМАЗОВЫ стали в XX веке объектом парадоксальных философских и психоаналитических интерпретаций.', '39000', ''),
(4, 'Доктор Живаго', 'В 1958 году Борис Пастернак был удостоен Нобелевской премии по литературе "за значительные достижения в современной лирической поэзии, а также за продолжение традиций великого русского эпического романа", но для соотечественников присуж-дение премии оказалось прочно связано с романом "Доктор Живаго". Масштабная эпопея, захватывающая история любви, трагическое свидетельство многострадальной эпохи, - это произведение по праву считается одним из величайших романов как российской, так и мировой литературы.', '51000', ''),
(5, 'Думай как математик. Как решать любые задачи быстрее и эффективнее', 'О чем книга\n\nПринято считать, что математики - это люди, наделенные недюжинными интеллектуальными способностями, которые необходимо развивать с самого детства. И большинству точность и логичность математического мышления недоступна. Барбара Оакли, доктор наук, доказывает, что каждый может изменить способ своего мышления и овладеть приемами, которые используют все специалисты по точным наукам. \n\n\nПочему книга достойна прочтения\n\nИз этой книги вы узнаете: \n\nпочему важно усваивать знания порциями; \n\nкак преодолеть "ступор" и добиться озарения; \n\nкакую роль играет сон в решении сложных задач; \n\nчто такое прокрастинация, и как с ней бороться; \n\nпочему практика вспоминания гораздо эффективнее, чем перечитывание несколько раз одного и того же; \n\nчто такое "интерливинг", и почему он так полезен для запоминания и усвоения новой информации. \n\n\nКто автор\n\nБарбара Оакли, доктор наук, инженер-консультант, член совета Американского института медицинского и биологического машиностроения. Барбара сменила несколько профессий: была переводчиком с русского языка на советском траулере в Беринговом море, работала преподавателем в Китае, служила в войсках связи США, в Западной Германии командиром отделения связистов. Она на своем личном опыте доказала, что человек способен тренировать свой мозг и осваивать новые, казавшиеся недоступными, области знаний. \n\n\nКлючевые понятия\n\nМозг, математика, естественные науки, тренировка, обучение, знания, задача, прокрастинация, информация.', '61000', ''),
(7, 'Git для профессионального программиста', 'Эта книга представляет собой обновленное руководство по использованию Git в современных условиях. С тех пор как проект Git - распределенная система управления версиями - был создан Линусом Торвальдсом, прошло много лет, и система Git превратилась в доминирующую систему контроля версий, как для коммерческих целей, так и для проектов с открытым исходным кодом. Эффективный и хорошо реализованный контроль версий необходим для любого успешного веб-проекта. Постепенно эту систему приняли на вооружение практически все сообщества разработчиков ПО с открытым исходным кодом. Появление огромного числа графических интерфейсов для всех платформ и поддержка IDE позволили внедрить Git в операционные системы семейства Windows. Второе издание книги было обновлено для Git-версии 2.0 и уделяет большое внимание GitHub.', '77500', ''),
(8, 'Путь аналитика. Практическое руководство IT-специалиста', 'Перед вами настольная книга для системных аналитиков, программистов, архитекторов программного обеспечения, менеджеров проектов и начальников отделов по разработке программ. Кроме того, книга будет полезным учебным пособием для преподавателей, студентов и аспирантов кафедр IТ в технических вузах. Как воплотить неясные ожидания заказчика в блестящий и прибыльный проект? Как избежать ошибок на начальном этапе? Как стать эффективным аналитиком?\r\nАвторы отвечают на эти вопросы и делятся своими ноу-хау, которые позволят вам стать гуру в разработке программного обеспечения. Главное достоинство книги - ее практическая направленность. В ней собрана полезная информация со ссылками на теоретические материалы из разных областей разработки программного обеспечения: анализа, архитектуры, управления проектами, лидерства и управления персоналом - все, что понадобится в реальных производственных проектах.\r\nПомимо этого, в книге содержится анализ разнообразных кейсов и ситуаций, а также примеры документов и шаблонов, необходимых для разработки ПО. Авторы структурируют огромный массив теоретической информации исходя из ее практической ценности на каждом этапе профессиональной карьеры. Книга написана простым и доступным языком.\r\nАвторы 15 лет шли к высшему уровню профессионализма, а вас отделяет от тех же знаний только прочтение этой книги.', '67900', ''),
(9, 'Простой Python. Современный стиль программирования', 'Эта книга идеально подходит как для начинающих программистов, так и для тех, кто только собирается осваивать Python, но уже имеет опыт программирования на других языках. В ней подробно рассматриваются самые современные пакеты и библиотеки Python. Стилистически издание напоминает руководство с вкраплениями кода, подробно объясняя различные концепции Python 3. Под обложкой вы найдете обширный материал от самых основ языка до сравнительно сложных и узких тем. \r\n\r\nПрочитав эту книгу, вы не только убедитесь, что Python - это вкусно, но и освоите искусство тестирования, отладки, многократного использования кода, а также научитесь применять Python в различных предметных областях.', '101900', ''),
(10, 'Самоучитель UML 2', 'Рассмотрена современная технология объектно-ориентированного анализа и проектирования программных систем и бизнес-процессов в контексте нотации унифицированного языка моделирования UML 2. Подробно изложены все понятия языка UML 2 в полном соответствии с оригинальной спецификацией последней версии этого языка. Приведены конкретные рекомендации по разработке канонических диаграмм языка и рассмотрены особенности разработки моделей с помощью CASE-средства Borland® Together® Designer. Описана нотация OCL - языка объектных ограничений, по которому практически отсутствует информация на русском.\r\n', '26400', ''),
(11, 'Язык программирования C++. Лекции и упражнения', 'Книга представляет собой тщательно проверенный, качественно составленный полноценный учебник по одной из ключевых тем для программистов и разработчиков. Эта классическая работа по вычислительной технике обучает принципам программирования, среди которых структурированный код и нисходящее проектирование, а также использованию классов, наследования, шаблонов, исключений, лямбда-выражений, интеллектуальных указателей и семантики переноса.\r\nАвтор и преподаватель Стивен Прата создал поучительное, ясное и строгое введение в С++. Фундаментальные концепции программирования излагаются вместе с подробными сведениями о языке С++. Множество коротких практических примеров иллюстрируют одну или две концепции за раз, стимулируя читателей осваивать новые темы за счет непосредственной их проверки на практике. Вопросы для самоконтроля и упражнения по программированию, предлагаемые в конце каждой главы, помогут читателям сосредоточиться на самой критически важной информации и систематизировать наиболее сложные концепции.\r\nНаписанное в дружественном стиле, простое в освоении руководство для самостоятельного изучения подойдет как студентам, обучающимся программированию, так и разработчикам, имеющим дело с другими языками и стремящимся лучше понять фундаментальные основы этого ключевого языка программирования. Шестое издание этой книги обновлено и расширено с учетом последних тенденций в разработке на С++, а также с целью детального отражения нового стандарта С++11.', '219000', ''),
(15, 'Анна Каренина', 'кое-что', '12300', '11/ff/f530fccbb02b3f548504a5bcdd9b9e42.jpg');

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
(60, 'root@localhost', 'update', '2017-01-02 18:08:05', 15);

-- --------------------------------------------------------

--
-- Table structure for table `books_properties`
--

CREATE TABLE `books_properties` (
  `books_book_id` int(9) UNSIGNED NOT NULL,
  `authors` tinytext,
  `date_of_release` year(4) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `printing` int(9) DEFAULT NULL,
  `books_img` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `books_properties`
--

INSERT INTO `books_properties` (`books_book_id`, `authors`, `date_of_release`, `language`, `printing`, `books_img`) VALUES
(2, 'Михаил Булгаков', 2016, 'русский', 12000, NULL),
(3, 'Фёдор Достоевский', 2014, 'русский', 15000, NULL),
(4, 'Борис Пастернак', 2013, 'русский', 10000, NULL);

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
(2, 'Художественная литература', 1, 8, 13),
(3, 'Классическая и современная проза', 2, 9, 10),
(4, 'Кинороманы', 2, 11, 12),
(5, 'Учебная литература', 1, 2, 7),
(6, 'Технические науки', 2, 5, 6),
(8, 'Русская литература', 1, 14, 15),
(20, 'Естественные науки', 2, 3, 4);

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
(8, 2),
(8, 3),
(8, 4),
(6, 5),
(6, 7),
(6, 8),
(6, 9),
(6, 10),
(6, 11),
(8, 15);

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
  MODIFY `book_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `books_log`
--
ALTER TABLE `books_log`
  MODIFY `books_log_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;
--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(9) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
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
  ADD CONSTRAINT `fk_books_properties_books1` FOREIGN KEY (`books_book_id`) REFERENCES `books` (`book_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `categories_has_books`
--
ALTER TABLE `categories_has_books`
  ADD CONSTRAINT `fk_categories_has_books_books1` FOREIGN KEY (`books_book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_categories_has_books_categories1` FOREIGN KEY (`categories_category_id`) REFERENCES `categories` (`category_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `fk_Feedbacks_Users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Feedbacks_books1` FOREIGN KEY (`books_id`) REFERENCES `books` (`book_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
