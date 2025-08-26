import 'package:flutter/material.dart';
import 'package:colocs/models/meeting.dart';
import 'package:colocs/models/minutes.dart';
import 'package:colocs/services/api_service.dart';
import 'package:colocs/widgets/custom_text_field.dart';
import 'package:colocs/widgets/custom_button.dart';

class MinutesScreen extends StatefulWidget {
  final Meeting meeting;

  const MinutesScreen({Key? key, required this.meeting}) : super(key: key);

  @override
  _MinutesScreenState createState() => _MinutesScreenState();
}

class _MinutesScreenState extends State<MinutesScreen> {
  final _formKey = GlobalKey<FormState>();
  final _contentController = TextEditingController();
  final _difficultiesController = TextEditingController();
  final _recommendationsController = TextEditingController();
  
  // Contrôleurs pour les résultats des villages
  final _peopleToEnrollController = TextEditingController();
  final _peopleEnrolledController = TextEditingController();
  final _cmuCardsAvailableController = TextEditingController();
  final _cmuCardsDistributedController = TextEditingController();
  final _complaintsReceivedController = TextEditingController();
  final _complaintsProcessedController = TextEditingController();

  String _status = 'draft';
  bool _isLoading = false;
  Minutes? _existingMinutes;

  @override
  void initState() {
    super.initState();
    _loadExistingMinutes();
  }

  @override
  void dispose() {
    _contentController.dispose();
    _difficultiesController.dispose();
    _recommendationsController.dispose();
    _peopleToEnrollController.dispose();
    _peopleEnrolledController.dispose();
    _cmuCardsAvailableController.dispose();
    _cmuCardsDistributedController.dispose();
    _complaintsReceivedController.dispose();
    _complaintsProcessedController.dispose();
    super.dispose();
  }

  Future<void> _loadExistingMinutes() async {
    try {
      setState(() => _isLoading = true);
      
      // Charger les minutes existantes depuis l'API
      final minutes = await ApiService().getMeetingMinutes(widget.meeting.id);
      
      if (minutes != null) {
        setState(() {
          _existingMinutes = minutes;
          _contentController.text = minutes.content ?? '';
          _difficultiesController.text = minutes.difficulties ?? '';
          _recommendationsController.text = minutes.recommendations ?? '';
          _peopleToEnrollController.text = minutes.peopleToEnrollCount?.toString() ?? '';
          _peopleEnrolledController.text = minutes.peopleEnrolledCount?.toString() ?? '';
          _cmuCardsAvailableController.text = minutes.cmuCardsAvailableCount?.toString() ?? '';
          _cmuCardsDistributedController.text = minutes.cmuCardsDistributedCount?.toString() ?? '';
          _complaintsReceivedController.text = minutes.complaintsReceivedCount?.toString() ?? '';
          _complaintsProcessedController.text = minutes.complaintsProcessedCount?.toString() ?? '';
          _status = minutes.status ?? 'draft';
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur lors du chargement: $e')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _saveMinutes() async {
    if (!_formKey.currentState!.validate()) return;

    try {
      setState(() => _isLoading = true);

      final minutesData = {
        'content': _contentController.text.trim(),
        'difficulties': _difficultiesController.text.trim(),
        'recommendations': _recommendationsController.text.trim(),
        'people_to_enroll_count': int.tryParse(_peopleToEnrollController.text) ?? 0,
        'people_enrolled_count': int.tryParse(_peopleEnrolledController.text) ?? 0,
        'cmu_cards_available_count': int.tryParse(_cmuCardsAvailableController.text) ?? 0,
        'cmu_cards_distributed_count': int.tryParse(_cmuCardsDistributedController.text) ?? 0,
        'complaints_received_count': int.tryParse(_complaintsReceivedController.text) ?? 0,
        'complaints_processed_count': int.tryParse(_complaintsProcessedController.text) ?? 0,
        'status': _status,
      };

      if (_existingMinutes != null) {
        // Mise à jour
        await ApiService().updateMeetingMinutes(_existingMinutes!.id, minutesData);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Compte rendu mis à jour avec succès')),
        );
      } else {
        // Création
        await ApiService().createMeetingMinutes(widget.meeting.id, minutesData);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Compte rendu créé avec succès')),
        );
      }

      Navigator.pop(context, true); // Retour avec succès
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur lors de la sauvegarde: $e')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _saveAsDraft() async {
    setState(() => _status = 'draft');
    await _saveMinutes();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Compte Rendu de Réunion'),
        backgroundColor: Colors.blue[600],
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // En-tête avec informations de la réunion
                    _buildMeetingHeader(),
                    const SizedBox(height: 24),
                    
                    // Section 1: Contenu du compte rendu
                    _buildContentSection(),
                    const SizedBox(height: 24),
                    
                    // Section 2: Difficultés et recommandations
                    _buildDifficultiesRecommendationsSection(),
                    const SizedBox(height: 24),
                    
                    // Section 3: Résultats des villages
                    _buildVillageResultsSection(),
                    const SizedBox(height: 24),
                    
                    // Section 4: Statut
                    _buildStatusSection(),
                    const SizedBox(height: 32),
                    
                    // Boutons d'action
                    _buildActionButtons(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildMeetingHeader() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.meeting.title ?? 'Sans titre',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Date: ${widget.meeting.scheduledDate ?? 'Non définie'}',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            if (widget.meeting.scheduledTime != null)
              Text(
                'Heure: ${widget.meeting.scheduledTime}',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            if (widget.meeting.location != null)
              Text(
                'Lieu: ${widget.meeting.location}',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildContentSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Contenu du compte rendu',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            CustomTextField(
              controller: _contentController,
              label: 'Contenu principal',
              hint: 'Résumé détaillé de la réunion, points abordés, décisions prises...',
              maxLines: 6,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDifficultiesRecommendationsSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Difficultés et recommandations',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            
            // Difficultés
            CustomTextField(
              controller: _difficultiesController,
              label: 'Difficultés rencontrées *',
              hint: 'Décrivez les difficultés rencontrées pendant la réunion (minimum 10 caractères)...',
              maxLines: 4,
              validator: (value) {
                if (value == null || value.trim().length < 10) {
                  return 'Ce champ est obligatoire et doit contenir au moins 10 caractères';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            
            // Recommandations
            CustomTextField(
              controller: _recommendationsController,
              label: 'Recommandations *',
              hint: 'Formulez vos recommandations et suggestions d\'amélioration (minimum 10 caractères)...',
              maxLines: 4,
              validator: (value) {
                if (value == null || value.trim().length < 10) {
                  return 'Ce champ est obligatoire et doit contenir au moins 10 caractères';
                }
                return null;
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildVillageResultsSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Résultats des villages',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Statistiques et résultats obtenus lors de la réunion',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 16),
            
            // Enrôlements
            _buildEnrollmentSection(),
            const SizedBox(height: 16),
            
            // Cartes CMU
            _buildCmuSection(),
            const SizedBox(height: 16),
            
            // Réclamations
            _buildComplaintsSection(),
          ],
        ),
      ),
    );
  }

  Widget _buildEnrollmentSection() {
    return Container(
      padding: const EdgeInsets.all(12.0),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Enrôlements',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          
          Row(
            children: [
              Expanded(
                child: CustomTextField(
                  controller: _peopleToEnrollController,
                  label: 'Personnes à enrôler',
                  keyboardType: TextInputType.number,
                  validator: (value) {
                    if (value != null && value.isNotEmpty) {
                      final number = int.tryParse(value);
                      if (number == null || number < 0) {
                        return 'Veuillez entrer un nombre positif';
                      }
                    }
                    return null;
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: CustomTextField(
                  controller: _peopleEnrolledController,
                  label: 'Personnes enrôlées',
                  keyboardType: TextInputType.number,
                  validator: (value) {
                    if (value != null && value.isNotEmpty) {
                      final number = int.tryParse(value);
                      if (number == null || number < 0) {
                        return 'Veuillez entrer un nombre positif';
                      }
                      
                      final toEnroll = int.tryParse(_peopleToEnrollController.text);
                      if (toEnroll != null && number > toEnroll) {
                        return 'Ne peut pas dépasser le nombre à enrôler';
                      }
                    }
                    return null;
                  },
                ),
              ),
            ],
          ),
          
          // Affichage du taux d'enrôlement
          if (_peopleToEnrollController.text.isNotEmpty && 
              _peopleEnrolledController.text.isNotEmpty)
            _buildProgressIndicator(
              'Taux d\'enrôlement',
              _calculateEnrollmentRate(),
              Colors.blue,
            ),
        ],
      ),
    );
  }

  Widget _buildCmuSection() {
    return Container(
      padding: const EdgeInsets.all(12.0),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Cartes CMU',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          
          Row(
            children: [
              Expanded(
                child: CustomTextField(
                  controller: _cmuCardsAvailableController,
                  label: 'Cartes disponibles',
                  keyboardType: TextInputType.number,
                  validator: (value) {
                    if (value != null && value.isNotEmpty) {
                      final number = int.tryParse(value);
                      if (number == null || number < 0) {
                        return 'Veuillez entrer un nombre positif';
                      }
                    }
                    return null;
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: CustomTextField(
                  controller: _cmuCardsDistributedController,
                  label: 'Cartes distribuées',
                  keyboardType: TextInputType.number,
                  validator: (value) {
                    if (value != null && value.isNotEmpty) {
                      final number = int.tryParse(value);
                      if (number == null || number < 0) {
                        return 'Veuillez entrer un nombre positif';
                      }
                      
                      final available = int.tryParse(_cmuCardsAvailableController.text);
                      if (available != null && number > available) {
                        return 'Ne peut pas dépasser le nombre disponible';
                      }
                    }
                    return null;
                  },
                ),
              ),
            ],
          ),
          
          // Affichage du taux de distribution
          if (_cmuCardsAvailableController.text.isNotEmpty && 
              _cmuCardsDistributedController.text.isNotEmpty)
            _buildProgressIndicator(
              'Taux de distribution',
              _calculateCmuDistributionRate(),
              Colors.green,
            ),
        ],
      ),
    );
  }

  Widget _buildComplaintsSection() {
    return Container(
      padding: const EdgeInsets.all(12.0),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Réclamations',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          
          Row(
            children: [
              Expanded(
                child: CustomTextField(
                  controller: _complaintsReceivedController,
                  label: 'Réclamations reçues',
                  keyboardType: TextInputType.number,
                  validator: (value) {
                    if (value != null && value.isNotEmpty) {
                      final number = int.tryParse(value);
                      if (number == null || number < 0) {
                        return 'Veuillez entrer un nombre positif';
                      }
                    }
                    return null;
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: CustomTextField(
                  controller: _complaintsProcessedController,
                  label: 'Réclamations traitées',
                  keyboardType: TextInputType.number,
                  validator: (value) {
                    if (value != null && value.isNotEmpty) {
                      final number = int.tryParse(value);
                      if (number == null || number < 0) {
                        return 'Veuillez entrer un nombre positif';
                      }
                      
                      final received = int.tryParse(_complaintsReceivedController.text);
                      if (received != null && number > received) {
                        return 'Ne peut pas dépasser le nombre reçu';
                      }
                    }
                    return null;
                  },
                ),
              ),
            ],
          ),
          
          // Affichage du taux de traitement
          if (_complaintsReceivedController.text.isNotEmpty && 
              _complaintsProcessedController.text.isNotEmpty)
            _buildProgressIndicator(
              'Taux de traitement',
              _calculateComplaintsProcessingRate(),
              Colors.orange,
            ),
        ],
      ),
    );
  }

  Widget _buildProgressIndicator(String label, double percentage, Color color) {
    return Padding(
      padding: const EdgeInsets.only(top: 12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                label,
                style: Theme.of(context).textTheme.bodySmall,
              ),
              Text(
                '${percentage.toStringAsFixed(0)}%',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          LinearProgressIndicator(
            value: percentage / 100,
            backgroundColor: Colors.grey[300],
            valueColor: AlwaysStoppedAnimation<Color>(color),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Statut du compte rendu',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Choisissez le statut approprié pour ce compte rendu',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 16),
            
            DropdownButtonFormField<String>(
              value: _status,
              decoration: const InputDecoration(
                labelText: 'Statut',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'draft', child: Text('Brouillon')),
                DropdownMenuItem(value: 'pending_validation', child: Text('En attente de validation')),
                DropdownMenuItem(value: 'published', child: Text('Publié')),
              ],
              onChanged: (value) {
                if (value != null) {
                  setState(() => _status = value);
                }
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionButtons() {
    return Row(
      children: [
        Expanded(
          child: CustomButton(
            onPressed: _saveAsDraft,
            text: 'Enregistrer en brouillon',
            backgroundColor: Colors.grey[600],
            textColor: Colors.white,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: CustomButton(
            onPressed: _saveMinutes,
            text: _status == 'pending_validation' 
                ? 'Soumettre pour validation' 
                : 'Enregistrer',
            backgroundColor: Colors.blue[600],
            textColor: Colors.white,
          ),
        ),
      ],
    );
  }

  // Méthodes de calcul des taux
  double _calculateEnrollmentRate() {
    final toEnroll = int.tryParse(_peopleToEnrollController.text);
    final enrolled = int.tryParse(_peopleEnrolledController.text);
    
    if (toEnroll != null && enrolled != null && toEnroll > 0) {
      return (enrolled / toEnroll) * 100;
    }
    return 0;
  }

  double _calculateCmuDistributionRate() {
    final available = int.tryParse(_cmuCardsAvailableController.text);
    final distributed = int.tryParse(_cmuCardsDistributedController.text);
    
    if (available != null && distributed != null && available > 0) {
      return (distributed / available) * 100;
    }
    return 0;
  }

  double _calculateComplaintsProcessingRate() {
    final received = int.tryParse(_complaintsReceivedController.text);
    final processed = int.tryParse(_complaintsProcessedController.text);
    
    if (received != null && processed != null && received > 0) {
      return (processed / received) * 100;
    }
    return 0;
  }
}
