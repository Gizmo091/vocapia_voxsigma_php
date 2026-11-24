# Changelog

## 3.0.0

### Rupture de compatibilité

**`Trans::model()` force la langue en CLI, comme en REST**

Les drivers CLI et REST exécutent désormais le même traitement. `model('fre')` seul émet `-l:fre` en CLI au lieu de `-lfre` : `vrxs_lid` ne tourne plus avant la transcription. Cela vaut aussi dans un `Pipeline`, sauf si un étage `lid()` précède `trans()` : la langue étant déjà identifiée, `model('fre')` et `forceLanguage(false)` y émettent `-lfre`, et seul `forceLanguage()` explicite émet `-l:fre`. Le comportement REST de `model()` seul ne change pas.

| Requête | CLI | REST |
|---|---|---|
| `model('fre')`, avec ou sans `forceLanguage()` | `-l:fre` | `model=fre` |
| `model('fre')->forceLanguage(false)` | `-lfre -qx1` | `model=fre`, `qopt=x1` |
| `model('fre')->multilingual(true)` | `-lfre -qxs` | `model=fre`, `qopt=xs` |
| `multilingual()` sans modèle | `-qx` | `qopt=x` |
| `model('und')` | `-dl30`, sauf si `lidDuration()` ou `multilingual()` | `model=und` |

- **Pour relancer la LID en CLI**, utiliser `forceLanguage(false)`, ou `multilingual()` si plusieurs langues sont possibles.
- `model()` + `forceLanguage()` + `multilingual()` lève une `InvalidArgumentException` : les deux demandes se contredisent.
- `lidDuration()`, `lidThreshold()` et `lidVersion()` sont transmis tels quels mais n'activent pas la LID.

### Corrections

- REST : `forceLanguage()` n'envoie plus `forcelang`, un argument que le serveur rejette.
- `sadModel()` : le modèle SAD ne s'enchaîne pas avec les autres lettres, contrairement à ce qu'indiquait la 2.10.0. Il est émis à part (`-qdj -q@sad2`, deux champs `qopt` en REST).
- `quality()` est émis à part (`-q2 -qdxs`) : accolé à `multilingual()`, il était lu comme un nombre de langues, et en REST `qopt=2d` perdait le `d`.
- Les lettres de `-q` / `qopt` ne dépendent plus de l'ordre d'appel par rapport à `multilingual()` (`dxs`).
- REST : `executeAsync()` et `toCurl(async: true)` n'envoyaient aucun paramètre (modèle compris).
- `Pipeline` : les options du premier étage étaient perdues avec `input()`.

Tests : `php tests/DriverParityTest.php`.

---

## 2.10.0

### Nouveautés

**`Part` : options de `vrxs_part` v1.4**

Les lettres de `-q` (CLI) / `qopt` (REST) s'enchaînent : `->dualChannel()->includeNonSpeech()->outputSpeakerModel()->sadModel('sad2')` produit `-qdji@sad2` / `qopt=dji@sad2`. Le modèle SAD est toujours placé en dernier.

- `includeNonSpeech()` : inclure les segments sans parole (`-qj` / `qopt=j`).
- `sadModel(string)` : modèle de détection de parole (`-q@<model>` / `qopt=@<model>`).

Options CLI uniquement, l'API REST ne les accepte pas :

- `speakerThreshold(float)` (`-a`), `knownSpeakerPrior(float)` (`-b`), `timeout(int)` (`-e`)
- `segmentFile(string)` (`-i`), `speakerEmbeddingsFile(string)` (`-sc`), `workingDir(string)` (`-w`)
- `withDtmf(bool|int)` (`-x` ou `-x<num>`), `sequenceId(string)` (`-y`)
- Réglages fins : `segmentPenalty()` (`--alpha`), `clusterPenalty()` (`--beta`), `clusteringThreshold()` (`--cthr`), `collar()` (`--collar`), `maxSegmentDuration()` (`--msdur`), `sadDownsampling()` (`--sads`), `sadSegmentPenalty()` (`--spty`), `speechFramePenalty()` (`--sfpty`)

### Corrections

- `Part` : `vrxs_part` n'a pas d'option de langue. `model()` n'émet plus `-l<model>` en CLI ; il reste transmis en REST (argument `model`), comme avant.
- Mode CLI : `priority()` n'émet plus `-p <n>`. L'option est REST uniquement, et en CLI `-p` active l'indice de progression tandis que la valeur était lue comme fichier d'entrée.

---

## 2.9.1

### Corrections

- Mode CLI : les binaires VoxSigma héritent désormais de l'environnement complet du processus PHP (`PATH`, `HOME`, `LANG`…). Auparavant seules les variables `VRXS_*` leur étaient transmises : ils ne trouvaient plus les outils appelés par leur nom (sox, ffmpeg, file…) et échouaient avec « invalid audio file » sur des fichiers valides.
- Mode CLI : les variables `VRXS_*` sont transmises même lorsque `$_ENV` est vide (`variables_order` sans `E`, comme dans php.ini-production).
- Test de non-régression : `php tests/CliDriverEnvironmentTest.php`.

---

## 2.9.0

### Nouveautés

**Méthode `xvfind`** (binaire `vr_xvfind`, CLI uniquement)

Recherche de locuteurs par empreinte vocale (*speaker embeddings*) : les locuteurs du fichier d'essai (`trialFile`, *search file*) sont recherchés dans la base de référence (`refFile`, *database file*), qui recense les SPM à fouiller. Chacun des deux fichiers peut contenir un ou plusieurs locuteurs (recherches 1:N, N:1 et N:N).

```php
$response = $vox->xvfind()
    ->trialQueries(
        SpeakerQueryList::create()
            ->add('SPK_A', 1, 'spk1', '/path/to/interview.spm')
            ->addQuery(2, 'spk3', '/path/to/meeting.spm')  // Identifiant auto
    )
    ->referenceFiles(
        FileList::create()
            ->add('/path/to/archive1.spm')
            ->add('/path/to/archive2.spm')
    )
    ->cohortFile('/path/to/cohort.xv')  // -c
    ->cohortShortListSize(200)          // -n
    ->prior(0.01)                       // -p
    ->threshold(0.70)                   // -t
    ->run();

foreach (SpeakerHit::fromOutput($response->getBody()) as $hit) {
    echo "{$hit->queryId} : {$hit->speakerId} dans {$hit->spmFile} ({$hit->score})\n";
}
```

Les chemins de fichiers déjà constitués restent utilisables via `refFile()` et `trialFile()`, et `output()` (argument positionnel) redirige les résultats vers un fichier au lieu de la sortie standard.

**Modèles `SpeakerQueryList` et `SpeakerQuery`**

Construction du fichier d'essai sans gérer de fichier temporaire. Une requête par ligne : `<id> <canal> <spkid> <spm>`. L'identifiant est repris tel quel sur chaque résultat ; il n'a pas besoin d'être unique, mais `hasDuplicateIds()` permet de le vérifier, les résultats de requêtes homonymes n'étant plus distinguables. Les espaces sont refusés dans l'identifiant et le `spkid` (`\InvalidArgumentException`), le format étant séparé par des espaces ; le chemin du SPM, qui termine la ligne, les accepte.

**Modèle `SpeakerHit`**

Lecture des résultats : `SpeakerHit::fromOutput()` transforme la sortie en objets (`queryId`, `score`, `channel`, `speakerId`, `spmFile`) et ignore les lignes hors format, notamment les messages ajoutés par `verbose()`.

---

## 2.8.0

### Nouveautés

**`Part` : sortie du modèle de locuteurs (`.spm`)**

Nouvelle option `outputSpeakerModel()` sur `Part` (CLI `-qi`, REST `qopt=i`). Lorsqu'elle est activée, la méthode effectue le partitionnement et retourne un fichier binaire SPM (modèle acoustique des locuteurs).

```php
$response = $vox->part()
    ->model('fre')
    ->file('/path/audio.wav')
    ->outputSpeakerModel()
    ->run();

if ($response->isSpm()) {
    file_put_contents('/path/speakers.spm', $response->getBody());
} else {
    // Erreur — le corps contient un document XML d'erreur
    echo $response->getXml();
}
```

Sur `Response`, deux nouveaux helpers :

- `Response::getBody()` : alias de `getXml()` pour les payloads non-XML (le SPM est un binaire).
- `Response::isSpm()` : vrai si le corps commence par les octets ASCII `HAR` (magic bytes SPM), faux sinon (typiquement une erreur XML).

Se combine avec les autres flags `-q` / `qopt` : par exemple `->dualChannel()->outputSpeakerModel()` produit `-qdi` (CLI) / `qopt=di` (REST).

---

## 2.7.0

### Améliorations

**`speakerCount` : support du mode par canal (`dualChannel`)**

La méthode `speakerCount()` (option `-k` / `kopt`) accepte désormais des valeurs indépendantes pour chaque canal audio en mode `dualChannel`. Les formats supportés sont :

| Format | Signification |
|---|---|
| `5` | 5 locuteurs pour tous les canaux |
| `2:5` | Entre 2 et 5 locuteurs pour tous les canaux |
| `5,3` | Canal 1 = 5, Canal 2 = 3 |
| `2:5,1:3` | Canal 1 = 2 à 5, Canal 2 = 1 à 3 |

```php
// Tous canaux (inchangé)
->speakerCount(max: 5)
->speakerCount(min: 2, max: 5)

// Par canal (nécessite dualChannel)
->dualChannel()->speakerCount(max: 5, channel2Max: 3)
->dualChannel()->speakerCount(min: 2, max: 5, channel2Min: 1, channel2Max: 3)
```

Une `\InvalidArgumentException` est levée si le format multi-canal est utilisé sans `dualChannel()`.

---

## 2.6.1

### Corrections

- Nom de fichier temporaire pour la liste de langues désormais déterministe (basé sur un hash du contenu), évitant la création de fichiers dupliqués.

---

## 2.6.0

### Nouveautés

**Modèle `LanguageList`** pour `Trans` et `Lid`

Nouveau modèle `LanguageList` permettant de passer une liste de langues de façon fluide, sans gérer manuellement un fichier temporaire.

```php
$trans->languageList(
    LanguageList::create()
        ->add('fre')
        ->add('eng-usa')
        ->add('eng-gbr')
);
```

---

## 2.5.1

### Corrections

- Correction du paramètre de durée de `Lid` (`-d`) pour supporter les formats min/max de durée de segment.

---

## 2.5

### Nouveautés

- Option `multilingual` pour `Trans` et `Lid`.
- Option `speakerCount` avec min/max pour `Trans` et `Part`.
- Support de la détection de mots-clés (`vrxs_kws`) avec `KeywordList` et gestion du poids.
- Ajout de `xml2kar` et renommage de `inputListFile` en `inputKarList`.

### Améliorations

- Support du logging dans les drivers CLI et REST.
- Variable d'environnement `VRXS_ROOT` pour localiser les binaires CLI.
- Méthode `toCli()` pour déboguer les commandes CLI.
- Méthode `toCurl()` pour déboguer les requêtes REST.
- Validation de l'existence des binaires dans `CliDriver`.
- Déduplication des chemins dans `FileList`.

### Corrections

- Correction de la combinaison des options `-q` (`dualChannel`, `noPartitioning`, `quality`).
- Correction de `ParameterCollection` pour les options CLI/REST partagées.
- Les paramètres REST sont désormais envoyés en POST fields (et non en URL params).
- Correction de `dualChannel` dans `Part.php` pour utiliser `-q`.