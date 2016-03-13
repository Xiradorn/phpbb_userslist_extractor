## phpBB UserList Extractor  
Un comodo script, perfettamente integrato in phpBB, che permette l'estrazione degli utenti in un comodo file scaricabile.  
E' possibile configurare il file con tre estensioni possibili: testuale ( txt ), gzip ( gz ) e bzip2 ( bz2 )  
E' anche escludere nomi dal proprio elenco attraverso il loro username o anche lo user_id.

### Come usare il file
* Scaricate il file,
* Copiate nella root,
* lanciate e bon voyage.

### Configurazione
Nel file sono presenti due parametri
```
$cfg = array(
	'file_type'						=> 'txt',
	'num_righe_per_query'			=> 100,
	'exclude_usernames'				=> array(),
	'exclude_usernames_id'			=> array()
);
```

### Parametri
**file_type**  
Questo parametro vi permette di salvare il vostro file nei seguenti formati.
* txt - Testo
* gz - gzip - il file avrà un formato ovviamente .tar.gz
* bz2 - bzip2 - il file avrà un formato ovviamente .tar.bz2

**num_righe_per_query**  
Queso parametro vi permette di effettuare più o meno query. Valori consigliati bassi se il vostro host è economico o non sopporta query molto lunge

**exclude_usernames_id**  
Questo parametro è un Array. Vi permette di escludere alcuni nomi dalla vostra lista inserendo gli USER_ID da escludere dal vostro elenco.  
Questo parametro è prioritario rispetto a _**exclude_usernames**_ quindi non riempitelo se volete usare l'escusione tramite Username.  

_**Esempio**_
```
$cfg = array(
	'file_type'						=> 'txt',
	'num_righe_per_query'			=> 100,
	'exclude_usernames'				=> array(
		'10', '22', '234'
	),
	'exclude_usernames_id'			=> array()
);
```

**exclude_usernames**  
Questo parametro è un Array. Vi permette di escludere alcuni nomi dalla vostra lista inserendo gli Username da escludere dal vostro elenco.  
Questo parametro è secondario rispetto al precedente.

_**Esempio**_ 
```
$cfg = array(
	'file_type'						=> 'txt',
	'num_righe_per_query'			=> 100,
	'exclude_usernames'				=> array(),
	'exclude_usernames_id'			=> array(
		'Xiradorn', 'User1', 'Tester'
	)
);
```

**Author**
Sir Xiradorn - [XiradornLab][link]

[link]: http://xiradorn.it
