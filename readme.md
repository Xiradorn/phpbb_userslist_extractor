### phpBB UserList Extractor  
Un semplice estrattore di utenti per phpbb.

**Come usare il file**  
* Scaricate il file,
* Copiate nella root,
* lanciate e bon voyage.

**Configurazione**  
Nel file sono presenti due parametri
```
$config = array(
	'direct_download' 	            => true,
	'file_type'                     => 'gz',
    'num_righe_per_query'           => 100
);
```
Il Primo serve per configurare se scaricare direttamente (true) o sul server (false).
Il Secondo serve per scaricare un file di tipo gzip (gz) o testo (text).
Il Terzo vi permette di usare meno query ma più grandi. Non superate i 500 se avete un host economico
