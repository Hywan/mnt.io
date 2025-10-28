+++
title = "Bye bye WhatsApp, hello ?"
date = "2021-01-19"
description = "A rare article written in French discussing WhatsApp and its alternatives."
+++

Ce billet est en français, essentiellement à destination de mes ami(e)s
et familles, il sert à vulgariser très rapidement les enjeux autour de
[WhatsApp](https://www.whatsapp.com/),
[Signal](https://www.signal.org/fr/), [Telegram](https://telegram.org/)
et [Matrix](https://element.io/) (*spoiler*, c'est le gagnant). Tout le
monde me pose la même question, alors voici une réponse rapidement en
brouillon qui va m'éviter du copier/coller !

Je ne rentre volontairement *pas* dans les détails. Il faut que ce
document reste à la portée de tous, sans aucune connaissance en réseau,
chiffrement, sécurité etc. Ceux qui ont ces connaissances savent déjà
que Matrix est *le* réseau vers lequel aller ;-).

## Les bases

Quand on parle de messageries, il y a 2 choses primordiales plus 1 bonus :

- le chiffrement ;
- la topologie du réseau (c'est facile, n'ayez pas peur) ;
- l'accès libre et gratuit sans restriction au code source (*open
  source*).

Nous pouvons aussi parler du modèle économique du réseau rapidement,
voir le tableau comparatif.

### Le chiffrement

Pour respecter la vie privée et éviter l'espionnage et le vol des
données, il faut que le chiffrement se fasse de bout en bout (*end to
end encryption*). Ça veut dire que vous seul avez la clé pour chiffrer
et/ou déchiffrer vos messages, et personne d'autre. Par message,
j'entends message texte, audio, image, vidéo, appels audios-vidéos,
tout. Vos données sont à vous, et uniquement vous, et personne ne peut
les utiliser, à part la personne avec qui vous les partagez (qui elle,
normalement à une clé de déchiffrement par exemple). Les clés servent
aussi à identifier la personne avec qui vous parlez, ça permet d'éviter
le vol d'identité.

### La topologie

La plupart des réseaux sont centralisés : ça veut dire qu'on a un gros
silot, un énorme ordinateur/serveur, et que tout le monde est dessus. Ça
pose plein de problèmes :

- impossible d'avoir le contrôle dessus ;
- impossible de faire confiance ;
- faille unique.

Je prends l'exemple de WhatsApp pour illustrer tout ça parce que ça
parle à tout le monde : Facebook décide unilatéralement de déchiffrer le
réseau, personne n'a le contrôle dessus, c'est une violation grave de la
vie privée de milliards de personnes et on ne peut rien faire (à part
quitter le réseau). Avions-nous confiance dans ce que faisait Facebook
avec nos données WhatsApp avant ? Non, aucunement. Ils disaient que
c'était chiffré, l'était-ce vraiment ? J'accorde plus de confiance dans
ceux qui ont créé et chiffré le réseau avant qu'il ne soit racheté par
Facebook, donc j'ai envie d'y croire, mais… *je ne peux pas le vérifier*
! Pourquoi ? Parce que personne (en dehors de quelques employés chez
Facebook) n'a accès au code source, aux programmes, qui font tourner
WhatsApp. Et pour le côté *faille unique*, si Facebook est attaqué,
c'est l'entièreté du réseau qui s'effondre, c'est une faille unique, un
*single point of failure* comme on dit dans le métier. Pareil si le
réseau est *hacké*, c'est un accès illimité à tout le réseau.

> Aucune transparence = aucune confiance.

Mais il existe une alternative majeure bien sûr ! Les réseaux
décentralisés. Au lieu d'avoir un serveur, il y a en des centaines, des
milliers. Il n'y a plus de contrôle possible. Il n'y a plus de *single
point of failure*. Un *hacker* ne peut accéder au pire qu'aux données
d'un seul serveur, pas de tous les serveurs (il existe pleins
d'exceptions mais je vulgarise, hein). Nous pouvons créer autant de
serveurs que nous le souhaitons. Souvent, ce sont des réseaux open
source, donc nous pouvons lire le code des programmes, vérifier qu'ils
font bien ce qu'ils proclament faire.

## Tableau comparatif

Comparons les services populaires avec ces critères de bases.

<figure>

  <table>
    <tbody>
      <tr>
        <td><strong>Service</strong></td>
        <td><strong>Chiffrement</strong></td>
        <td><strong>Topologie</strong></td>
        <td><strong>Open source</strong></td>
      </tr>
      <tr>
        <td><strong>WhatsApp</strong></td>
        <td>bout en bout (pour le moment)</td>
        <td>centralisé (US)</td>
        <td>non</td>
      </tr>
      <tr>
        <td><strong>Telegram</strong></td>
        <td>bout en bout (pour le moment)</td>
        <td>centralisé (Dubaï, US)</td>
        <td>non</td>
      </tr>
      <tr>
        <td><strong>Signal</strong></td>
        <td>bout en bout</td>
        <td>centralisé (US)</td>
        <td>oui mais…</td>
      </tr>
      <tr>
        <td><strong>Matrix</strong></td>
        <td>bout en bout</td>
        <td>décentralisé</td>
        <td>oui</td>
      </tr>
    </tbody>
  </table>

  <figcaption>

  Comparons la base !

  </figcaption>

</figure>

Signal est open source, mais nous ne pouvons pas vérifier ce qui est
installé sur les serveurs, parce que le serveur est privé. De plus, le
serveur open source n'a pas été [mis à jour depuis avril
2020](https://github.com/signalapp/Signal-Server), en année
Informatique, c'est très long. Ça cache quelque chose ? Aucune idée, je
ne peux pas le savoir, car je n'ai pas d'éléments pour prendre une
décision. Est-ce que je veux déposer mes données privées sur un service
dans lequel je n'ai pas confiance ?

En plus, Signal comme WhatsApp sont hébergés/situés aux US, avec les
lois liberticides qu'on leur connaît bien (comme le Cloud Act). Signal
limite la casse grâce au chiffrement de bout en bout, mais peut être
qu'une *backdoor* est présente et qu'on ne le saura jamais.

> Aucune transparence = aucune confiance

Les réseaux décentralisés sont supérieurs à tous les niveaux (pas de
contrôle, pas de hack massif etc.). Les réseaux open source sont ceux en
qui nous pouvons avoir confiance. Donc le choix est vite fait, le
gagnant ici est Matrix.

Comparons maintenant comment les services sont financés, parce que c'est
important. Si un service n'est pas rentable, il pourrait avoir de
l'appétit pour les données de ses utilisateurs, et là c'est dangereux
(c'est exactement ce qu'il se passe avec Facebook et WhatsApp).

<figure>

  <table>
    <tbody>
      <tr>
        <td><strong>Service</strong></td>
        <td><strong>Revenues</strong></td>
      </tr>
      <tr>
        <td><strong>WhatsApp</strong></td>
        <td>Facebook veut utiliser les données privées pour vendre de la publicité ciblée.</td>
      </tr>
      <tr>
        <td><strong>Telegram</strong></td>
        <td>Les fondateurs sont millionnaires et injectent de l'argent.<br>Dans peu de temps, financement via pubs et comptes premiums.</td>
      </tr>
      <tr>
        <td><strong>Signal</strong></td>
        <td>Organisation à but non-lucratif qui opère via des dons.</td>
      </tr>
      <tr>
        <td><strong>Matrix</strong></td>
        <td>Matrix développe, offre ou vend des services autour du réseau, mais pas autour des données !</td>
      </tr>
    </tbody>
  </table>

  <figcaption>

  Comment sont financés les services ?

  </figcaption>

</figure>

Les gagnants ici sont Signal et Matrix.

## Conclusion : Matrix gagnant

Dans le cas des réseaux centralisés, Signal est une meilleure
alternative à WhatsApp et Telegram de part son mode de financement (donc
son appétit pour les données des utilisateurs), mais ils sont tous
sujets aux même problèmes : aucune confiance car pas de transparence,
hébergés aux US etc.

Mais les réseaux décentralisés sont supérieurs car ils résolvent tous
ces problèmes ! Matrix est décentralisé, est financé par des services
autour du réseau mais pas par les données du réseau (qui sont
inaccessibles de toute façon, elles n'existent que sur vos téléphones et
ordinateurs, nul part ailleurs).

J'utilise Matrix. Je vous conseille d'utiliser Matrix. Partir sur
Signal, c'est sortir de la gueule d'un loup pour aller dans celle d'un
autre. Je suis admiratif du travail des développeurs de chez Signal, ils
sont vraiment bons, leur protocole de chiffrement est magnifique, mais
je n'ai pas confiance dans leur service parce que je ne *peux* pas. Et
personne ne le *peut*.

J'utilise aussi WhatsApp et Signal pour rester en contact avec mes amis
et ma famille, et leur dire d'utiliser Matrix, mais je n'y publierai
jamais de données personnelles, photos ou quoi que ce soit, je n'ai
aucune confiance. Libre à vous aussi d'utiliser plusieurs réseaux, après
tout nous jonglons déjà avec plusieurs réseaux (mail, SMS, WhatsApp,
Matrix, Twitter, [Mastodon](https://mastodon.social/about) etc.), ça
n'est pas un problème !

## Premier pas avec Matrix

C'est parti, petit tuto Matrix. Le réseau est exceptionnel, mais le
client officiel ([Element](https://element.io/)) est encore un peu «
brut » à utiliser comparé à Signal ou WhatsApp. Notez que ça évolue très
très vite (je compte 616 contributeurs qui travaillent dessus
bénévolement, encore une grande force de l'open source !).

Ce qui va vous titiller le plus c'est : vous ne pouvez pas toujours
identifier vos contacts par numéro de téléphone (seulement s'ils sont
enregistrés sur un serveur d'identité). Pourquoi ? Parce que votre
compte à un identifiant, comme une adresse email. Le mien est
`@mnt_io:matrix.org` (le format est `@identifiant:serveur`). C'est bien
meilleur pour la vie privée. Et pis, ça n'est pas différent de MSN ou de
tout autre réseau de l'époque, c'est vraiment WhatsApp qui a imposé la «
découvertabilité » par le numéro de téléphone. Bien que très pratique,
c'est dangereux pour la vie privée.

Donc, go, on installe le client :

- sur [iOS, macOS
  etc](https://apps.apple.com/us/app/element-messenger/id1083446067).,
- sur
  [Android](https://play.google.com/store/apps/details?id=im.vector.app&hl=en_US&gl=US),
- sur votre [bureau ou votre
  navigateur](https://element.io/get-started).

Puis on crée un compte, et ajoutez moi. C'est parti !

Matrix/Element est basé sur les groupes. Les chats « directs » (1:1)
sont des groupes aussi. Vous pouvez même rejoindre des *rooms* (gros
groupes, des communautés) avec des centaines voire des milliers de
personnes dedans. C'est très flexible.

Parce que c'est open source, n'importe qui peut écrire son propre client
(programme qui se connecte au réseau). Il existe des clients
alternatives, comme [Nio](https://nio.chat/) ou
[FluffyChat](https://fluffychat.im/en/), ou même [Ditto
Chat](https://matrix.org/docs/projects/client/ditto-chat). Tous ces
clients sont encore en beta, mais ça montre un futur très excitant pour
Matrix avec des clients de plus en plus aboutis !

### Matrix, Element, Vector, hein ?

- Element c'est le nom de l'entreprise qui travaille/développe le réseau, les
  serveurs et le client ;
- Matrix c'est le nom du réseau ;
- Vector, c'est l'ancien nom d'Element.

On parle souvent de façon indifférentiée de Matrix ou Element, c'est un
abus de langage.

> Before: Mark as read.
>
> Now: Mark has read.

Et par pitié, quittez Facebook…
