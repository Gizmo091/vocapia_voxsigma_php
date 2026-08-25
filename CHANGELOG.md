# Changelog

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