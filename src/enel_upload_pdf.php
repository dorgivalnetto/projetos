<?php

carregar_arquivo();


function carregar_arquivo(){
    $diretorio = 'upload/' . date('Y-m') . '/';
    $arquivo   = $_FILES['file']['name'];
    $pdfFileType = strtolower(pathinfo($arquivo,PATHINFO_EXTENSION));

    //carrega o arquivo
    if($pdfFileType != "pdf") {
        echo "<p style='color:red; text-align:center'>
        Apenas arquivos com a extensão PDF são aceitos!</p>";
    } 
    
    else if(!is_dir($diretorio)){
        mkdir($diretorio . '/', 0700, true);
        move_uploaded_file($_FILES['file']['tmp_name'], $diretorio . basename($arquivo)); 
        $localizacao = __DIR__ .$diretorio.$arquivo;
        listar_arquivos_no_diretorio($diretorio);
    } 
    
    else if (file_exists($diretorio.$arquivo)){
        echo "Arquivo já existe";

    } else{
        move_uploaded_file($_FILES['file']['tmp_name'], $diretorio . basename($arquivo)); 
        $localizacao = __DIR__ .$diretorio.$arquivo;
        listar_arquivos_no_diretorio($diretorio);
    }
    
    //print_r($localizacao);
    return $localizacao;
}


function listar_arquivos_no_diretorio($diretorio){
    chdir($diretorio);
    foreach (glob("*.*") as $arquivo) {
        echo "<a href='".$diretorio.$arquivo."'>".$arquivo."</a><br />";
    }
}
 
?>