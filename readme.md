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
	'direct_download' 	=> true,
	'file_type'			=> 'gz'
);
```
Il primo serve per configurare se scaricare direttamente (true) o sul server (false).
Il secondo serve per scaricare un file di tipo gzip (gz) o testo (text).
