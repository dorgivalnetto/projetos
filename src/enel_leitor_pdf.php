<?php

//include dados de conexão
//include "testa_upload.php";

use Sabberworm\CSS\Value\Size;

require 'vendor/autoload.php';

//extract($_POST);

$dir = isset($_GET['dir']) ? 'src/' . 'upload/' . date('Y-m') . '/' . '/'.$_GET['dir']: 'src/' . 'upload/' . date('Y-m') . '/' . '/';

$itens = new DirectoryIterator("$dir");
   foreach($itens as $item){
    if($item->gettype() === 'dir'){
        $diretorios[] = $item->getFilename();
    }else{
        $arquivos[] = $item->getFilename();
    }
}


foreach($arquivos as $arquivo){
    echo "\n --------- \n";
    $localizacao =  $dir . $arquivo;
    ler_conteudo_pdf($localizacao);
    extrair_conteudo_do_pdf($data);
}

function ler_conteudo_pdf($localizacao){
    $parser = new \Smalot\PdfParser\Parser();

    //$link do testa_upload
    $pdf = $parser->parseFile($localizacao);

    global $data;
    $data = $pdf->getPages()[0]->getDataTm();
    //var_dump($data);

    return $data;
}


function extrair_conteudo_do_pdf($data){
    $num_uc = $data[17][1];
    echo "Unidade Consumidora ", $num_uc, "\n";

    $num_cl = $data[18][1];
    echo "Número do Cliente ", $num_cl, "\n";
    
    $mes_ano = $data[19][1];
    $competencia = explode(' ', $mes_ano);
    $competencia = $competencia[0];
    echo "Competência (Mês/Ano) ", $competencia, "\n";

    $vencimento = $data[19][1];
    $venc = explode(' ', $vencimento);
    $venc = $venc[1];
    //formato americano data
    $venc = implode('-', array_reverse(explode('/', $venc)));
    echo "Vencimento ", $venc, "\n";

    $valor_pagar = $data[19][1];
    $val_pag = explode(' ', $valor_pagar);
    $val_pag = formatar_valor_pagar($val_pag);
    echo "Total a Pagar R$ ", $val_pag, "\n";
      
    $nome = $data[20][1];
    echo "Nome ", $nome, "\n";

    $rua = $data[21][1];
    echo "Rua ", $rua, "\n";

    $bairro = $data[22][1];
    echo "Bairro ", $bairro, "\n";
    
    $cep = $data[23][1];
    $cep = str_replace('CEP: ', '', $cep); // remove a palavra CEP:
    echo "CEP:", $cep; 

    persistir_no_banco($num_uc, $num_cl, $competencia, $venc, $val_pag, $nome, $rua, $bairro, $cep);
}


function persistir_no_banco($num_uc, $num_cl, $competencia, $venc, $val_pag, $nome, $rua, $bairro, $cep){
    $servername = "mysql62-farm2.uni5.net";
    $database = "programaeficie";
    $username = "programaeficie";
    $password = "u7skGe";

    //create connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    //check connection
    if(!$conn){
        die("Connection failed: " . mysqli_connect_error());
    } 
    echo "Connected sucessfuly \n";
    
    mysqli_select_db($conn, $database);

    $create_table_enderecos = 
        "CREATE TABLE IF NOT EXISTS `enel_enderecos` (
        `endereco_id` int(11) NOT NULL AUTO_INCREMENT,
        `nome` varchar(100) NOT NULL,
        `rua` varchar(100) NOT NULL,
        `bairro` varchar(60) NOT NULL,
        `cep` varchar(9) NOT NULL,
        PRIMARY KEY (`endereco_id`), 
        UNIQUE KEY `cunique_table_nome` (`nome`, `rua`, `cep`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    
    $create_table_dados_pdf = 
        "CREATE TABLE IF NOT EXISTS `enel_dados_pdf` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `unid_cons` int(11) NOT NULL,
        `num_cliente` int(11) NOT NULL,
        `competencia` varchar(7) NOT NULL,
        `vencimento` date NOT NULL,
        `valor_a_pagar` float NOT NULL,
        `endereco_id` int(11) NOT NULL,
        `variacao_valor_pago_mes_anterior` float DEFAULT 0 NOT NULL,
         PRIMARY KEY (`id`), 
         UNIQUE KEY `cunique_table_uc_competencia` (`unid_cons`,`competencia`)
        )ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=latin1;";

    mysqli_query($conn, $create_table_enderecos);
    mysqli_query($conn, $create_table_dados_pdf);

    $verifica_uc = mysqli_query($conn, "SELECT unid_cons, competencia FROM enel_dados_pdf WHERE unid_cons = $num_uc AND competencia = '$competencia'");
    echo mysqli_num_rows($verifica_uc);
    $verifica_end = mysqli_query($conn, "SELECT nome, rua, cep FROM enel_enderecos WHERE nome LIKE '%$nome%' AND rua like '%$rua%' AND cep like '$cep%'");
    echo mysqli_num_rows($verifica_end);

    if (mysqli_num_rows($verifica_end)>0){
        $sql_dados = "INSERT INTO enel_dados_pdf (unid_cons, num_cliente, competencia, vencimento, valor_a_pagar, endereco_id, variacao_valor_pago_mes_anterior) VALUES 
        ('$num_uc', '$num_cl', '$competencia', '$venc', $val_pag, (SELECT endereco_id FROM enel_enderecos WHERE nome LIKE '%$nome%' AND rua like '%$rua%' AND cep like '$cep%'), variacao_valor_pago_mes_anterior)";
        echo "endereço já cadastrado!";
        if (mysqli_query($conn, $sql_dados)) {
            echo "New record created successfully \n";
        } else {
            echo "Error: " . $sql_dados . "<br>" . mysqli_error($conn);
        }
    } else{
        $sql_endereco = "INSERT INTO enel_enderecos (nome, rua, bairro, cep) VALUES 
        ('$nome', '$rua', '$bairro', '$cep')";
        if (mysqli_query($conn, $sql_endereco)) {
            echo "New record created successfully \n";
        } else {
            echo "Error: " . $sql_endereco . "<br>" . mysqli_error($conn);
        }

        if (mysqli_num_rows($verifica_uc) > 0){
            //echo mysqli_num_rows($verifica_uc);
            echo "Fatura já cadastrada!";
        } else{
            $sql_dados = "INSERT INTO enel_dados_pdf (unid_cons, num_cliente, competencia, vencimento, valor_a_pagar, endereco_id, variacao_valor_pago_mes_anterior) VALUES 
            ($num_uc, $num_cl, '$competencia', '$venc', $val_pag, (SELECT endereco_id FROM enel_enderecos WHERE nome LIKE '%$nome%' AND rua LIKE '%$rua%' and cep LIKE '%$cep%'))";
            if (mysqli_query($conn, $sql_dados)) {
                echo "New record created successfully \n";
            } else {
                echo "Error: " . $sql_dados . "<br>" . mysqli_error($conn);
            }
        }     
    }

    economia_mes_anterior($conn, $num_uc);
}

function formatar_valor_pagar($val_pag){
    $str = $val_pag[2];
    $str = str_replace('R$', '', $str); // remove o R$
    $str = str_replace('.', '', $str); // remove o ponto
    $str = str_replace(',', '.', $str); // troca a vírgula por ponto
    return $str; // resulta em 99999.99
}

function economia_mes_anterior($conn, $num_uc){
    
    mysqli_query($conn, "UPDATE enel_dados_pdf SET variacao_valor_pago_mes_anterior = CAST(IFNULL(valor_a_pagar - (SELECT edp.valor_a_pagar FROM enel_dados_pdf edp WHERE enel_dados_pdf.id < edp.id ORDER BY edp.id DESC LIMIT 0,1), 0) AS DECIMAL(10,2)) WHERE unid_cons = $num_uc");
}

mysqli_close($conn);
?>