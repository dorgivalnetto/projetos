CREATE DATABASE enel;
USE enel;

CREATE TABLE IF NOT EXISTS `enel_enderecos` (
        `endereco_id` int(11) NOT NULL,
        `nome` varchar(100) NOT NULL,
        `rua` varchar(100) NOT NULL,
        `bairro` varchar(60) NOT NULL,
        `cep` varchar(9) NOT NULL,
        PRIMARY KEY (`endereco_id`), 
        UNIQUE KEY `cunique_table_nome` (`nome`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `enel_dados_pdf` (
        `id` int(11) NOT NULL,
        `num_cliente` int(11) NOT NULL,
        `unid_cons` int(11) NOT NULL,
        `competencia` varchar(7) NOT NULL,
        `vencimento` date NOT NULL,
        `total` float NOT NULL,
        `endereco_id` int(11) NOT NULL,
         PRIMARY KEY (`id`), 
         FOREIGN KEY (`endereco_id`) REFERENCES `enel_enderecos`,
         UNIQUE KEY `cunique_table_uc_competencia` (`unid_cons`,`competencia`)
        )ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=latin1;

