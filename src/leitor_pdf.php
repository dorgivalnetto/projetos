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
    $localizacao =  $dir . $arquivo;
    //$localizacao = 'src/12-2022/411002_ UC 1393915.pdf';
    if ($arquivo == '411002_ UC 33868791.pdf' || $arquivo == '411002_ UC 33838478.pdf' || $arquivo == '411002_ UC 33838463.pdf' || $arquivo == '411002_ UC 23342115.pdf'){
        ler_conteudo_pdf($localizacao);
        extrair_conteudo_do_pdf2($linhas);    
    } else{
        ler_conteudo_pdf($localizacao);
        extrair_conteudo_do_pdf($linhas);
    }
}

function ler_conteudo_pdf($localizacao){
    $parser = new \Smalot\PdfParser\Parser();

    //$link do testa_upload
    $pdf = $parser->parseFile($localizacao);

    //Conteúdo do pdf
    $text = $pdf->getText();

    //new line with line break
    //$pdfText = nl2br($text);

    $pdfText = str_replace("\n", '; ', $text);
    //echo $pdfText;

    global $linhas;
    $linhas = explode(';', $pdfText);
    return $linhas;
}

//$linhas = ler_conteudo_pdf($arquivo);
function extrair_conteudo_do_pdf($linhas){
    $numero_cliente = $linhas[11];
    $num_cl = explode(' ', $numero_cliente);
    $num_cl = $num_cl[1];
    echo "\n Número do Cliente ", $num_cl, "\n";

    $unidade_consumidora = $linhas[11];
    $num_uc = explode(' ', $unidade_consumidora);
    $num_uc = $num_uc[2];
    echo "Unidade Consumidora ", $num_uc, "\n";

    $mes_ano = $linhas[12];
    $competencia = explode(' ', $mes_ano);
    $competencia = $competencia[1];
    //$competencia = "09/2022";
    echo "Mês/Ano ", $competencia, "\n";

    $vencimento = $linhas[12];
    $venc = explode(' ', $vencimento);
    $venc = $venc[2];
    //formato americano data
    $venc = implode('-', array_reverse(explode('/', $venc)));
    echo "Vencimento ", $venc, "\n";

    $valor_pagar = $linhas[12];
    $val_pag = explode(' ', $valor_pagar);
    $val_pag = formatar_valor_pagar($val_pag);
    echo "Total a Pagar R$ ", $val_pag, "\n";

    $nome = trim($linhas[13]);
    $rua = $linhas[14];
    $bairro = $linhas[15];
    echo "Endereço Completo", $nome, " ", $rua, " ", $bairro, "\n";

    $cep = $linhas[16];
    $cep = str_replace('CEP: ', '', $cep); // remove o CEP:
    echo "CEP:", $cep; 

    persistir_no_banco($num_cl, $num_uc, $competencia, $venc, $val_pag, $nome, $rua, $bairro, $cep);
}

function extrair_conteudo_do_pdf2($linhas){
    $numero_cliente = $linhas[11];
    $num_cl = explode(' ', $numero_cliente);
    $num_cl = $num_cl[1];
    echo "\n Número do Cliente ", $num_cl, "\n";

    $unidade_consumidora = $linhas[12];
    $num_uc = explode(' ', $unidade_consumidora);
    $num_uc = $num_uc[1];
    echo "Unidade Consumidora ", $num_uc, "\n";

    $mes_ano = $linhas[13];
    $competencia = explode(' ', $mes_ano);
    $competencia = $competencia[1];
    echo "Mês/Ano ", $competencia, "\n";

    $vencimento = $linhas[13];
    $venc = explode(' ', $vencimento);
    $venc = $venc[2];
    //formato americano data
    $venc = implode('-', array_reverse(explode('/', $venc)));
    echo "Vencimento ", $venc, "\n";

    $valor_pagar = $linhas[13];
    $val_pag = explode(' ', $valor_pagar);
    $val_pag = formatar_valor_pagar($val_pag);
    echo "Total a Pagar R$ ", $val_pag, "\n";

    $nome = $linhas[14];
    $rua = $linhas[15];
    $bairro = $linhas[16];
    echo "Endereço Completo", $nome, " ", $rua, " ", $bairro, "\n";

    $cep = $linhas[17];
    $cep = str_replace('CEP: ', '', $cep); // remove o CEP:
    echo "CEP:", $cep; 

    persistir_no_banco($num_cl, $num_uc, $competencia, $venc, $val_pag, $nome, $rua, $bairro, $cep);

}

function persistir_no_banco($num_cl, $num_uc, $competencia, $venc, $val_pag, $nome, $rua, $bairro, $cep){
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

    $create_table_enderecos = mysqli_query($conn, 
        "CREATE TABLE IF NOT EXISTS `enel_enderecos` (
        `endereco_id` int(11) NOT NULL,
        `nome` varchar(100) NOT NULL,
        `rua` varchar(100) NOT NULL,
        `bairro` varchar(60) NOT NULL,
        `cep` varchar(9) NOT NULL,
        PRIMARY KEY (`endereco_id`), 
        UNIQUE KEY `cunique_table_nome` (`nome`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
      );
    
    $create_table_dados_pdf = mysqli_query($conn,
        "CREATE TABLE IF NOT EXISTS `enel_dados_pdf` (
        `id` int(11) NOT NULL,
        `num_cliente` int(11) NOT NULL,
        `unid_cons` int(11) NOT NULL,
        `competencia` varchar(7) NOT NULL,
        `vencimento` date NOT NULL,
        `total` float NOT NULL,
        `endereco_id` int(11) NOT NULL,
         PRIMARY KEY (`id`), 
         UNIQUE KEY `cunique_table_uc_competencia` (`unid_cons`,`competencia`)
        )ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=latin1;"
    );

    //mysqli_query($conn, $create_table_enderecos);
    //mysqli_query($conn, $create_table_dados_pdf);

    $verifica_uc = mysqli_query($conn, "SELECT unid_cons, competencia FROM enel_dados_pdf WHERE unid_cons = $num_uc AND competencia = '$competencia'");
    $verifica_end = mysqli_query($conn, "SELECT nome FROM enel_enderecos WHERE nome LIKE '%$nome%'");
    echo mysqli_num_rows($verifica_uc);
    echo mysqli_num_rows($verifica_end);

    if (mysqli_num_rows($verifica_uc) > 0 OR mysqli_num_rows($verifica_end)>0){
        $sql_dados = "INSERT INTO enel_dados_pdf (num_cliente, unid_cons, competencia, vencimento, total, endereco_id) VALUES 
        ($num_cl, $num_uc, '$competencia', '$venc', $val_pag, (SELECT endereco_id FROM enel_enderecos WHERE nome LIKE '%$nome%'))";
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
        $sql_dados = "INSERT INTO enel_dados_pdf (num_cliente, unid_cons, competencia, vencimento, total, endereco_id) VALUES 
        ($num_cl, $num_uc, '$competencia', '$venc', $val_pag, (SELECT endereco_id FROM enel_enderecos WHERE nome LIKE '%$nome%'))";
        if (mysqli_query($conn, $sql_dados)) {
            echo "New record created successfully \n";
        } else {
            echo "Error: " . $sql_dados . "<br>" . mysqli_error($conn);
        }
    }     
    }
    /*
    mysqli_query($conn, "DELIMITER //
        CREATE TRIGGER 'TRG_DIFERENCA_VALOR_PAGAR' AFTER INSERT ON  'enel_dados_pdf'
        FOR EACH ROW
        BEGIN


    DELIMITER ;");
    mysqli_close($conn);*/
}

//$resultado = mysqli_query($conn, "SELECT * FROM enel_dados_pdf INNER JOIN enel_enderecos ON enel_dados_pdf.endereco_id = enel_enderecos.endereco_id");

//exibir no index.html

// Consulta que pega todos os produtos e o nome da categoria de cada um
//$sql = "SELECT * FROM enel_dados_pdf INNER JOIN enel_enderecos ON enel_dados_pdf.endereco_id = enel_enderecos.endereco_id";
//$query = mysqli_query($conn, $sql);

//while ($fatura = mysqli_fetch_assoc($query)) {
  // Aqui temos o array $produto com todos os dados encontrados
//  echo 'Identificador do Cliente: ' . $fatura['id'] . '';
//  echo 'Número do Cliente: ' . $fatura['num_cliente'] . '';
//  echo 'Unidade Consumidora: ' . $fatura['unid_cons']. '';
//  echo 'Competência: ' . $fatura['competencia']. '';
//  echo 'Vencimento: ' . $fatura['vencimento']. '';
//  echo 'Valor a pagar: ' . $fatura['total']. '';
//  echo 'Endereço completo: ' . $fatura['nome']. '' ;
//  echo '<hr />';
//}

function formatar_valor_pagar($val_pag){
    $str = $val_pag[3];
    $str = str_replace('R$', '', $str); // remove o R$
    $str = str_replace('.', '', $str); // remove o ponto
    $str = str_replace(',', '.', $str); // troca a vírgula por ponto
    return $str; // resulta em 99999.99
}

function verificar_mes_anterior($mes_ano){
    $competencia = explode('/', $mes_ano);
    $mes_anterior = $competencia[0]-1;
    return $mes_anterior;
}

//economia_mes_anterior(formatar_valor_pagar($val_pag), verificar_mes_anterior($mes_ano));

function economia_mes_anterior($valor_atual, $mes_anterior){
    $economia = $valor_atual - $mes_anterior;
    if ($economia >= 0){
        echo "Você não economizou!", $economia;
    } else if ($economia <0){
        echo "Você economizou!", $economia;
    }
    return $economia;
}

//mysqli_close($conn);
?>