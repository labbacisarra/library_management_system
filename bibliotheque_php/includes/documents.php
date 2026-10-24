<?php
// Documents demandés lors de l'inscription.
// Pour ajouter / retirer un document : modifiez simplement cette liste.
//   clé => [libellé affiché, obligatoire ?]
$DOCS_REQUIS = [
    'piece_identite' => ['Pièce d\'identité (carte nationale ou passeport)', true],
    'justificatif'   => ['Carte d\'étudiant ou justificatif de domicile',    true],
];

const DOC_MAX_OCTETS = 2 * 1024 * 1024;                       // 2 Mo par document
const DOC_TYPES = [                                           // formats acceptés (type MIME => extension)
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
];
